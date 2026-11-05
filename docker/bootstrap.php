<?php
declare(strict_types=1);

/**
 * Inicialización de Mega Prime Import para Docker/Coolify.
 *
 * Responsabilidades:
 *  - Resolver la conexión MariaDB desde MARIADB_URL (o MP_DB_*).
 *  - Crear las bases si el usuario tiene permisos; si no, usar una sola base.
 *  - Generar config.local.php, config.php y sso.config.php desde el entorno.
 *  - Aplicar esquemas base y migraciones pendientes de docker/migrations.
 *  - Crear el primer administrador y el contenido DEMO solo la primera vez.
 *
 * Todo es idempotente: un redeploy no duplica datos ni sobrescribe contenido.
 */

const MPI_DEFAULT_ROOT = '/var/www/mpi';
const MPI_DATA_DIR = '/data';

function out(string $message): void
{
    fwrite(STDOUT, '[mpi] '.$message.PHP_EOL);
}

function fail(string $message): never
{
    fwrite(STDERR, '[mpi][ERROR] '.$message.PHP_EOL);
    exit(1);
}

function env_raw(string $key, ?string $default = null): ?string
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

function env_nonempty(string $key, string $default = ''): string
{
    $value = getenv($key);
    if ($value === false || trim($value) === '') {
        return $default;
    }
    return trim($value);
}

function env_bool(string $key, bool $default): bool
{
    $value = getenv($key);
    if ($value === false || trim($value) === '') {
        return $default;
    }
    return in_array(strtolower(trim($value)), ['1', 'true', 'yes', 'on', 'si', 'sí'], true);
}

function env_int(string $key, int $default): int
{
    $value = getenv($key);
    if ($value === false || !is_numeric($value)) {
        return $default;
    }
    return (int) $value;
}

function normalize_domain(string $value): string
{
    $value = trim($value);
    $value = preg_replace('~^[a-zA-Z][a-zA-Z0-9+.-]*://~', '', $value) ?? $value;
    $parts = preg_split('~/~', $value, 2);
    $value = $parts[0] ?? $value;
    return strtolower(trim($value));
}

function validate_domain(string $domain, string $label): string
{
    if (!preg_match('/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?$/', $domain) || str_contains($domain, '..')) {
        fail($label.' no es un dominio válido: "'.$domain.'". Usa solo el nombre, sin https://, puerto ni ruta.');
    }
    return $domain;
}

function sanitize_db_name(string $value, string $fallback): string
{
    $value = preg_replace('/[^A-Za-z0-9_]+/', '_', $value) ?? '';
    $value = trim($value, '_');
    return $value !== '' ? $value : $fallback;
}

/**
 * @return array{host:string,port:int,user:string,password:string,name:string}
 */
function parse_db_url(string $url): array
{
    $url = trim($url);
    if ($url === '') {
        fail('Falta MARIADB_URL. Ejemplo: mysql://usuario:clave@host:3306/base');
    }

    $parts = @parse_url($url);
    if (is_array($parts) && !empty($parts['host'])) {
        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['mysql', 'mariadb', 'pdo-mysql', 'mysql+pdo'], true)) {
            fail('MARIADB_URL debe empezar por mysql:// o mariadb://');
        }
        return [
            'host' => (string) $parts['host'],
            'port' => (int) ($parts['port'] ?? 3306),
            'user' => rawurldecode((string) ($parts['user'] ?? '')),
            'password' => rawurldecode((string) ($parts['pass'] ?? '')),
            'name' => ltrim(rawurldecode((string) ($parts['path'] ?? '')), '/'),
        ];
    }

    // Respaldo para contraseñas con caracteres que parse_url no tolera.
    if (preg_match('~^(?:mysql|mariadb)://([^:]+):(.*)@([^/:]+)(?::(\d+))?/([^?]+)~', $url, $m)) {
        return [
            'host' => $m[3],
            'port' => (int) (($m[4] ?? '') !== '' ? $m[4] : 3306),
            'user' => rawurldecode($m[1]),
            'password' => rawurldecode($m[2]),
            'name' => rawurldecode($m[5]),
        ];
    }

    fail('MARIADB_URL no tiene un formato válido. Usa mysql://usuario:clave@host:3306/base');
}

/**
 * @param array{host:string,port:int,user:string,password:string,name?:string} $db
 */
function build_pdo(array $db, bool $withDb): PDO
{
    $dsn = 'mysql:host='.$db['host'].';port='.(int) $db['port'].';charset=utf8mb4';
    if ($withDb) {
        $name = (string) ($db['name'] ?? '');
        if ($name === '') {
            throw new RuntimeException('Nombre de base vacío');
        }
        $dsn .= ';dbname='.$name;
    }

    return new PDO($dsn, (string) $db['user'], (string) $db['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
        PDO::ATTR_TIMEOUT => 5,
    ]);
}

/**
 * @param array{host:string,port:int,user:string,password:string,name?:string} $db
 */
function try_connect(array $db, bool $withDb = true): ?PDO
{
    try {
        return build_pdo($db, $withDb);
    } catch (Throwable) {
        return null;
    }
}

/**
 * @param array{host:string,port:int,user:string,password:string,name?:string} $db
 */
function connect_with_retries(array $db, string $label, bool $withDb, int $retries): PDO
{
    $retries = max(1, $retries);
    $last = null;

    for ($attempt = 1; $attempt <= $retries; $attempt++) {
        try {
            return build_pdo($db, $withDb);
        } catch (Throwable $e) {
            $last = $e;
            if ($attempt === 1) {
                out('Esperando MariaDB ('.$label.')...');
            }
            if ($attempt < $retries) {
                sleep(2);
            }
        }
    }

    fail('No se pudo conectar a MariaDB ('.$label.'): '.($last?->getMessage() ?? 'error desconocido'));
}

function db_exists(PDO $server, string $name): bool
{
    $stmt = $server->prepare('SELECT 1 FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = ? LIMIT 1');
    $stmt->execute([$name]);
    return (bool) $stmt->fetchColumn();
}

function create_db(PDO $server, string $name): bool
{
    try {
        $server->exec(
            'CREATE DATABASE IF NOT EXISTS `'.str_replace('`', '``', $name)
            .'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        return true;
    } catch (Throwable $e) {
        out('Aviso: no se pudo crear la base '.$name.' ('.$e->getMessage().')');
        return false;
    }
}

/**
 * @param array{host:string,port:int,user:string,password:string,name?:string} $baseCreds
 * @param array<string,string> $names
 * @param array<string,array{host:string,port:int,user:string,password:string,name?:string}> $credentials
 * @return array<string,string>
 */
function resolve_databases(
    array $baseCreds,
    string $baseName,
    string $mode,
    array $names,
    array $credentials,
    int $retries
): array {
    $server = connect_with_retries($baseCreds, 'servidor', false, $retries);

    if ($mode === 'single') {
        if ($baseName === '') {
            fail('MPI_DB_MODE=single requiere una base en MARIADB_URL o en MP_DB_NAME.');
        }
        if (!db_exists($server, $baseName) && !create_db($server, $baseName)) {
            fail('La base única "'.$baseName.'" no existe y no se pudo crear.');
        }
        foreach ($credentials as $key => $creds) {
            $db = $creds;
            $db['name'] = $baseName;
            if (try_connect($db, true) === null) {
                fail('El usuario de '.$key.' no puede acceder a la base única '.$baseName.'.');
            }
        }
        return array_fill_keys(array_keys($names), $baseName);
    }

    $allSeparate = true;
    foreach ($names as $key => $name) {
        if (!db_exists($server, $name) && !create_db($server, $name)) {
            $allSeparate = false;
            continue;
        }

        $db = $credentials[$key];
        $db['name'] = $name;
        if (try_connect($db, true) === null) {
            out('Aviso: el usuario no puede acceder a la base '.$name.' ('.$key.').');
            $allSeparate = false;
        }
    }

    if ($mode === 'separate') {
        if (!$allSeparate) {
            fail('MPI_DB_MODE=separate, pero no fue posible crear/acceder a las tres bases. Revisa los permisos del usuario MariaDB.');
        }
        return $names;
    }

    // auto: se prefieren tres bases; si no hay permisos, se usa la base de la URL.
    if ($allSeparate) {
        return $names;
    }

    if ($baseName !== '') {
        out('Aviso: se usará una sola base ("'.$baseName.'") para los tres esquemas porque el usuario no puede crear/acceder a tres bases separadas.');
        if (!db_exists($server, $baseName) && !create_db($server, $baseName)) {
            fail('La base única "'.$baseName.'" no existe y no se pudo crear.');
        }
        foreach ($credentials as $key => $creds) {
            $db = $creds;
            $db['name'] = $baseName;
            if (try_connect($db, true) === null) {
                fail('El usuario de '.$key.' no puede acceder a la base única '.$baseName.'.');
            }
        }
        return array_fill_keys(array_keys($names), $baseName);
    }

    fail('No se pudieron crear tres bases y MARIADB_URL no incluye una base. Crea las bases a mano o usa un usuario con permisos.');
}

function apply_sql_file(PDO $pdo, string $path): void
{
    $sql = file_get_contents($path);
    if ($sql === false) {
        throw new RuntimeException('No se pudo leer '.$path);
    }

    // Los esquemas usan comentarios de línea y una sentencia por línea.
    $sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
    $statements = preg_split('/;\s*(?:\r?\n|$)/', $sql) ?: [];

    foreach ($statements as $statement) {
        $statement = trim($statement);
        if ($statement !== '') {
            $pdo->exec($statement);
        }
    }
}

function ensure_migration_table(PDO $pdo): void
{
    $pdo->exec(
        'CREATE TABLE IF NOT EXISTS schema_migrations (
            migration VARCHAR(190) NOT NULL PRIMARY KEY,
            applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
    );
}

function migration_applied(PDO $pdo, string $key): bool
{
    $stmt = $pdo->prepare('SELECT 1 FROM schema_migrations WHERE migration = ? LIMIT 1');
    $stmt->execute([$key]);
    return (bool) $stmt->fetchColumn();
}

function run_migration(PDO $pdo, string $key, string $label, callable $runner): void
{
    ensure_migration_table($pdo);
    if (migration_applied($pdo, $key)) {
        return;
    }

    out('Aplicando '.$key.' en '.$label);
    $runner($pdo);

    $stmt = $pdo->prepare('INSERT INTO schema_migrations (migration) VALUES (?)');
    $stmt->execute([$key]);
}

/**
 * @param array<string,string> $baseline
 * @param list<string> $scopes
 */
function apply_migrations(PDO $pdo, string $label, array $baseline, array $scopes, string $migrationsDir): void
{
    ensure_migration_table($pdo);
    $dbName = (string) ($pdo->query('SELECT DATABASE()')->fetchColumn() ?: '');
    $lockName = 'mpi_migrate_'.substr(hash('sha256', $label.'|'.$dbName), 0, 24);

    $lock = $pdo->prepare('SELECT GET_LOCK(?, 60)');
    $lock->execute([$lockName]);
    if ((int) $lock->fetchColumn() !== 1) {
        fail('No se pudo obtener el bloqueo de migraciones para '.$label.'.');
    }

    try {
        foreach ($baseline as $key => $path) {
            run_migration($pdo, $key, $label, static function (PDO $p) use ($path): void {
                apply_sql_file($p, $path);
            });
        }

        $files = glob($migrationsDir.'/*.sql') ?: [];
        sort($files, SORT_STRING);

        foreach ($files as $file) {
            $base = basename($file);
            if (!preg_match('/^(tienda|identidad|liquidaciones|all)__([A-Za-z0-9._-]+)\.sql$/', $base, $matches)) {
                out('Aviso: migración ignorada por nombre inválido: '.$base);
                continue;
            }
            if (!in_array($matches[1], $scopes, true)) {
                continue;
            }
            run_migration($pdo, $base, $label, static function (PDO $p) use ($file): void {
                apply_sql_file($p, $file);
            });
        }
    } finally {
        $release = $pdo->prepare('SELECT RELEASE_LOCK(?)');
        $release->execute([$lockName]);
    }
}

function write_php_config(string $path, array $data): void
{
    $code = "<?php\n"
        ."// Archivo generado automáticamente por docker/bootstrap.php.\n"
        ."// No editar a mano: se reemplaza en cada arranque del contenedor.\n"
        .'return '.var_export($data, true).";\n";

    $dir = dirname($path);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        fail('No se pudo crear el directorio '.$dir);
    }

    $tmp = $path.'.tmp';
    if (file_put_contents($tmp, $code, LOCK_EX) === false) {
        fail('No se pudo escribir '.$tmp);
    }
    @chmod($tmp, 0640);
    if (!@rename($tmp, $path)) {
        @unlink($tmp);
        fail('No se pudo mover '.$tmp.' a '.$path);
    }
    @chown($path, 'www-data');
    @chgrp($path, 'www-data');
    @chmod($path, 0640);

    out('Configuración escrita: '.$path);
}

function remove_php_config(string $path): void
{
    if (is_file($path)) {
        @unlink($path);
        out('Configuración eliminada: '.$path);
    }
}

function resolve_app_key(string $fallbackSeed): string
{
    $envKey = trim(env_raw('MPI_APP_KEY', '') ?? '');
    if ($envKey !== '') {
        return strlen($envKey) >= 32 ? $envKey : hash('sha256', $envKey);
    }

    $file = MPI_DATA_DIR.'/app_key';
    $lockPath = $file.'.lock';
    $lock = @fopen($lockPath, 'c');
    if ($lock === false) {
        out('Aviso: no se pudo abrir el bloqueo '.$lockPath.'; se usa una clave derivada. Define MPI_APP_KEY para que sea estable.');
        return hash('sha256', $fallbackSeed);
    }
    @chmod($lockPath, 0600);

    try {
        if (!flock($lock, LOCK_EX)) {
            out('Aviso: no se pudo bloquear '.$lockPath.'; se usa una clave derivada. Define MPI_APP_KEY para que sea estable.');
            return hash('sha256', $fallbackSeed);
        }

        if (is_file($file)) {
            $key = trim((string) file_get_contents($file));
            if (strlen($key) >= 32) {
                return $key;
            }
        }

        $key = bin2hex(random_bytes(32));
        if (@file_put_contents($file, $key, LOCK_EX) === false) {
            out('Aviso: no se pudo guardar '.$file.'; se usa una clave derivada. Define MPI_APP_KEY para que sea estable.');
            return hash('sha256', $fallbackSeed);
        }
        @chmod($file, 0600);
        out('Clave de aplicación generada en '.$file.'.');
        return $key;
    } finally {
        if (is_resource($lock)) {
            @flock($lock, LOCK_UN);
            @fclose($lock);
        }
    }
}

function resolve_sso_secret(string $envName, string $client, string $appKey): string
{
    $value = trim(env_raw($envName, '') ?? '');
    if ($value !== '') {
        if (strlen($value) >= 32) {
            return $value;
        }
        out('Aviso: '.$envName.' tiene menos de 32 caracteres; se deriva un secreto seguro.');
    }
    return hash_hmac('sha256', 'mpi-sso-'.$client, $appKey);
}

/**
 * @return array{id:int,username:string,email:string,created:bool,password:?string}|null
 */
function ensure_admin(PDO $pdo, string $domainTienda): ?array
{
    if (!env_bool('MPI_SEED_ADMIN', true)) {
        out('MPI_SEED_ADMIN=0: no se crea administrador inicial.');
        return null;
    }

    $existing = $pdo->query('SELECT id,email,username,role FROM admin_users ORDER BY id ASC LIMIT 1')->fetch();
    if (is_array($existing)) {
        out('Administrador existente: '.$existing['username'].' <'.$existing['email'].'>. No se modifica.');
        return [
            'id' => (int) $existing['id'],
            'username' => (string) $existing['username'],
            'email' => (string) $existing['email'],
            'created' => false,
            'password' => null,
        ];
    }

    $email = strtolower(trim(env_nonempty('MPI_ADMIN_EMAIL', 'admin@'.$domainTienda)));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        fail('MPI_ADMIN_EMAIL no es un correo válido: '.$email);
    }

    $username = strtolower(trim(env_nonempty('MPI_ADMIN_USERNAME', 'admin')));
    $username = preg_replace('/[^a-z0-9._-]+/', '', $username) ?? '';
    if (strlen($username) < 4 || strlen($username) > 60) {
        fail('MPI_ADMIN_USERNAME debe tener entre 4 y 60 caracteres (a-z, 0-9, punto, guion, guion bajo).');
    }

    $password = env_raw('MPI_ADMIN_PASSWORD', '') ?? '';
    $generated = false;
    if ($password === '') {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#%*-_';
        $password = '';
        for ($i = 0; $i < 20; $i++) {
            $password .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $generated = true;
    } elseif (strlen($password) < 12 || strlen($password) > 1024) {
        fail('MPI_ADMIN_PASSWORD debe tener al menos 12 caracteres.');
    }

    $stmt = $pdo->prepare(
        "INSERT INTO admin_users (email, username, password_hash, role, active)
         VALUES (?, ?, ?, 'admin', 1)"
    );
    $stmt->execute([$email, $username, password_hash($password, PASSWORD_DEFAULT)]);
    $id = (int) $pdo->lastInsertId();

    if ($generated) {
        $file = MPI_DATA_DIR.'/initial_admin.txt';
        $content = "Mega Prime Import · administrador inicial\n"
            ."Usuario: ".$username."\n"
            ."Correo: ".$email."\n"
            ."Contraseña: ".$password."\n"
            ."Cambia esta contraseña al entrar.\n";
        if (@file_put_contents($file, $content, LOCK_EX) !== false) {
            @chmod($file, 0600);
            out('Contraseña inicial generada en '.$file.' (leer con: cat '.$file.').');
        } else {
            out('Aviso: no se pudo guardar la contraseña inicial en '.$file.'.');
        }
    }

    out('Administrador inicial creado: '.$username.' <'.$email.'>.');
    return [
        'id' => $id,
        'username' => $username,
        'email' => $email,
        'created' => true,
        'password' => $password,
    ];
}

function ensure_liquidaciones_admin(PDO $pdo, string $username, ?string $password): void
{
    if ($password === null || $password === '') {
        return;
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ($count > 0) {
        return;
    }

    $stmt = $pdo->prepare(
        "INSERT INTO users (username, display_name, password_hash, role, active)
         VALUES (?, 'Administrador', ?, 'admin', 1)"
    );
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT)]);
    out('Administrador local de liquidaciones creado (respaldo para SSO desactivado).');
}

function seed_liquidaciones(PDO $pdo, bool $seedDemo): void
{
    $defaults = [
        'brand_name' => 'MEGA PRIME IMPORT',
        'hero_kicker' => 'Cubiertas · Acabados · Maquinarias · Herramientas',
        'hero_title' => 'Materiales que transforman tus proyectos',
        'hero_text' => 'Descubre cubiertas y acabados para tu obra. Solicita una proforma con las medidas y presentaciones que necesitas.',
        'hero_image' => '',
        'primary_cta_text' => 'Explorar productos',
        'nav_categories' => 'Categorías',
        'nav_products' => 'Productos',
        'nav_contact' => 'Contacto',
        'categories_kicker' => 'Encuentra lo que necesitas',
        'categories_title' => 'Nuestras categorías',
        'products_kicker' => 'Productos y presentaciones',
        'products_title' => 'Catálogo de materiales',
        'contact_kicker' => 'Atención personalizada',
        'contact_title' => 'Construyamos tu próxima obra',
        'contact_text' => 'Selecciona los materiales en el carrito y solicita una proforma. Confirmaremos presentaciones, cantidades y valores antes de cualquier compra.',
        'footer_text' => 'Mega Prime Import · Soluciones para construcción en Ecuador.',
        'contact_address' => 'Ecuador',
        'contact_phone' => '',
        'contact_email' => '',
        'whatsapp' => '',
        'social_facebook' => '',
        'social_instagram' => '',
        'social_tiktok' => '',
        'site_notice' => 'Los valores de referencia están sujetos a confirmación al emitir la proforma. No se procesa pago en línea.',
        'privacy_notice' => 'Los datos de contacto se usan exclusivamente para gestionar y responder tu solicitud comercial. Para consultas sobre tus datos, comunícate mediante los datos de contacto publicados en este sitio. Antes de publicar, la empresa debe revisar y completar su aviso de privacidad.',
        'demo_mode' => $seedDemo ? '1' : '0',
    ];

    $stmt = $pdo->prepare('INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?, ?)');
    foreach ($defaults as $key => $value) {
        $stmt->execute([$key, $value]);
    }

    if (!$seedDemo) {
        return;
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    if ($count > 0) {
        out('Liquidaciones ya tiene categorías; no se agrega catálogo DEMO.');
        return;
    }

    $pdo->beginTransaction();
    try {
        $addCategory = $pdo->prepare(
            'INSERT INTO categories (name, description, sort_order, visible) VALUES (?, ?, ?, 1)'
        );
        $categoryIds = [];
        foreach (['Cubiertas', 'Acabados de construcción', 'Herramientas', 'Maquinarias'] as $index => $name) {
            $addCategory->execute([$name, 'Consulta nuestras opciones de '.strtolower($name).'.', $index + 1]);
            $categoryIds[] = (int) $pdo->lastInsertId();
        }

        $examples = [
            [0, 'Teja española PVC', 'TEJA-ESP', 'Cubierta PVC estilo español. Ficha y precio pendientes de confirmación.', ['3.066 m', '4.599 m', '5.475 m', '6.132 m']],
            [0, 'Teja Roma PVC', 'TEJA-ROMA', 'Cubierta PVC estilo Roma. Formatos sujetos a confirmación.', ['3.608 m', '4.264 m', '5.248 m']],
            [0, 'Teja trapezoidal PVC', 'TEJA-TRAP', 'Cubierta PVC trapezoidal. Consulta largos, colores y espesores.', ['Formato por consultar']],
            [0, 'Policarbonato', 'POLI', 'Planchas de policarbonato. Consulta disponibilidad y espesores.', ['Presentación por confirmar']],
            [0, 'Duratecho metálico', 'DURATECHO', 'Cubierta metálica para construcción. Consulta medidas y acabados.', ['Presentación por confirmar']],
            [1, 'Cielo raso PVC', 'CIELO-PVC', 'Paneles para cielo raso PVC. Consulta formato vigente.', ['Ancho útil 0.25 m', 'Ancho útil 0.30 m']],
            [1, 'Wall Panel', 'WALL', 'Revestimiento decorativo interior. Consulta colores y perfiles.', ['Presentación por confirmar']],
            [1, 'Piso SPC', 'SPC', 'Pisos SPC para interiores. Consulta colores y dimensiones.', ['Presentación por confirmar']],
            [1, 'Alucobond', 'ALU', 'Panel compuesto para fachadas. Consulta espesores y medidas.', ['Presentación por confirmar']],
            [2, 'Herramientas de instalación', 'HERR', 'Accesorios y herramientas para instalación de cubiertas.', ['Presentación por confirmar']],
            [3, 'Equipos para construcción', 'MAQ', 'Equipos y maquinaria de construcción: disponibilidad por consultar.', ['Presentación por confirmar']],
        ];

        $addProduct = $pdo->prepare(
            "INSERT INTO products (category_id, name, sku, description, base_price, unit, variants_json, visible, featured, sort_order)
             VALUES (?, ?, ?, ?, 0, 'unidad', ?, 1, ?, ?)"
        );
        foreach ($examples as $index => $example) {
            [$categoryIndex, $name, $sku, $description, $variantLabels] = $example;
            $variants = json_encode(
                array_map(static fn (string $label): array => ['label' => $label, 'price' => 0], $variantLabels),
                JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
            );
            $addProduct->execute([
                $categoryIds[$categoryIndex],
                $name,
                $sku,
                $description,
                $variants,
                $index < 3 ? 1 : 0,
                $index + 1,
            ]);
        }

        $pdo->commit();
        out('Catálogo DEMO de liquidaciones cargado (4 categorías, 11 productos).');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function seed_tienda_demo(PDO $pdo, string $fixturePath, int $adminId): void
{
    if (!is_file($fixturePath)) {
        out('Aviso: no existe '.$fixturePath.'; no se carga DEMO de tienda.');
        return;
    }

    $count = (int) $pdo->query('SELECT COUNT(*) FROM site_content')->fetchColumn();
    if ($count > 0) {
        out('La tienda ya tiene contenido CMS; no se sobrescribe.');
        return;
    }

    $raw = file_get_contents($fixturePath);
    if ($raw === false) {
        throw new RuntimeException('No se pudo leer '.$fixturePath);
    }
    $config = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($config) || ($config['demoMode'] ?? false) !== true) {
        throw new RuntimeException('El catálogo DEMO de la tienda no tiene demoMode=true.');
    }

    $config['revision'] = 1;
    $config['savedAt'] = gmdate('c');
    $json = json_encode($config, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO site_content (id, revision, config_json, updated_by) VALUES (1, 1, ?, ?)')
            ->execute([$json, $adminId]);
        $pdo->prepare('INSERT INTO site_content_history (revision, config_json, user_id) VALUES (1, ?, ?)')
            ->execute([$json, $adminId]);
        $pdo->commit();
        out('Contenido DEMO de la tienda cargado (revisión 1).');
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function main(): void
{
    foreach (['pdo_mysql', 'curl', 'openssl', 'fileinfo', 'mbstring', 'json'] as $extension) {
        if (!extension_loaded($extension)) {
            fail('Falta la extensión PHP requerida: '.$extension);
        }
    }

    $root = rtrim(env_nonempty('MPI_ROOT', MPI_DEFAULT_ROOT), '/');
    if (!is_dir($root.'/tienda') || !is_dir($root.'/acceso') || !is_dir($root.'/liquidaciones')) {
        fail('MPI_ROOT no contiene las tres aplicaciones: '.$root);
    }

    $domainTienda = validate_domain(normalize_domain(env_nonempty('MPI_DOMAIN_TIENDA', 'megaprimeimport.com')), 'MPI_DOMAIN_TIENDA');
    $domainAcceso = validate_domain(normalize_domain(env_nonempty('MPI_DOMAIN_ACCESO', 'acceso.megaprimeimport.com')), 'MPI_DOMAIN_ACCESO');
    $domainLiquidaciones = validate_domain(normalize_domain(env_nonempty('MPI_DOMAIN_LIQUIDACIONES', 'liquidaciones.megaprimeimport.com')), 'MPI_DOMAIN_LIQUIDACIONES');

    $url = env_nonempty('MARIADB_URL', env_nonempty('DATABASE_URL', ''));
    if ($url !== '') {
        $parsed = parse_db_url($url);
    } else {
        $host = env_nonempty('MP_DB_HOST', '');
        if ($host === '') {
            fail('Falta MARIADB_URL (o MP_DB_HOST/MP_DB_USER/MP_DB_PASSWORD/MP_DB_NAME).');
        }
        $parsed = [
            'host' => $host,
            'port' => env_int('MP_DB_PORT', 3306),
            'user' => env_nonempty('MP_DB_USER', ''),
            'password' => env_raw('MP_DB_PASSWORD', '') ?? '',
            'name' => env_nonempty('MP_DB_NAME', ''),
        ];
    }

    if ($parsed['host'] === '') {
        fail('MARIADB_URL no incluye host.');
    }
    if ($parsed['user'] === '') {
        fail('MARIADB_URL no incluye usuario.');
    }

    $baseCreds = [
        'host' => $parsed['host'],
        'port' => $parsed['port'],
        'user' => $parsed['user'],
        'password' => $parsed['password'],
    ];

    $prefix = sanitize_db_name(env_nonempty('MPI_DB_PREFIX', 'mpi'), 'mpi');
    $names = [
        'tienda' => sanitize_db_name(env_nonempty('MPI_DB_TIENDA', $prefix.'_tienda'), $prefix.'_tienda'),
        'identidad' => sanitize_db_name(env_nonempty('MPI_DB_IDENTIDAD', $prefix.'_identidad'), $prefix.'_identidad'),
        'liquidaciones' => sanitize_db_name(env_nonempty('MPI_DB_LIQUIDACIONES', $prefix.'_liquidaciones'), $prefix.'_liquidaciones'),
    ];

    $credentials = [];
    foreach (['tienda', 'identidad', 'liquidaciones'] as $key) {
        $overrideUser = getenv('MPI_DB_USER_'.strtoupper($key));
        $overridePass = getenv('MPI_DB_PASSWORD_'.strtoupper($key));
        $credentials[$key] = [
            'host' => $parsed['host'],
            'port' => $parsed['port'],
            'user' => ($overrideUser === false || trim($overrideUser) === '') ? $parsed['user'] : trim($overrideUser),
            // Una variable vacía significa "usar la contraseña de MARIADB_URL",
            // no una contraseña vacía.
            'password' => ($overridePass === false || $overridePass === '') ? $parsed['password'] : $overridePass,
            'name' => '',
        ];
    }

    $mode = strtolower(env_nonempty('MPI_DB_MODE', 'auto'));
    if (!in_array($mode, ['auto', 'separate', 'single'], true)) {
        fail('MPI_DB_MODE debe ser auto, separate o single.');
    }
    $retries = max(1, env_int('MPI_DB_CONNECT_RETRIES', 30));

    out('Dominios: tienda='.$domainTienda.' acceso='.$domainAcceso.' liquidaciones='.$domainLiquidaciones);
    out('Modo de base de datos solicitado: '.$mode);

    $resolved = resolve_databases($baseCreds, $parsed['name'], $mode, $names, $credentials, $retries);
    $singleMode = count(array_unique(array_values($resolved))) === 1;
    out('Bases resueltas: '.json_encode($resolved, JSON_UNESCAPED_SLASHES));

    $dbConfigs = [];
    $pdos = [];
    foreach (['tienda', 'identidad', 'liquidaciones'] as $key) {
        $db = $credentials[$key];
        $db['name'] = $resolved[$key];
        $dbConfigs[$key] = $db;
        $pdos[$key] = connect_with_retries($db, $key.'/'.$resolved[$key], true, $retries);
    }

    $appKey = resolve_app_key($url.'|'.$parsed['host'].'|mpi-app-key-v1');
    $ssoEnabled = env_bool('MPI_ENABLE_SSO', true);
    $ssoTienda = resolve_sso_secret('MPI_SSO_SECRET_TIENDA', 'tienda', $appKey);
    $ssoLiquidaciones = resolve_sso_secret('MPI_SSO_SECRET_LIQUIDACIONES', 'liquidaciones', $appKey);

    $storeUrl = 'https://'.$domainTienda;
    $accesoUrl = 'https://'.$domainAcceso;
    $liquidUrl = 'https://'.$domainLiquidaciones;
    $redirectTienda = $storeUrl.'/admin/sso_callback.php';
    $redirectLiquidaciones = $liquidUrl.'/sso_callback.php';

    write_php_config($root.'/tienda/app/config.local.php', [
        'host' => $dbConfigs['tienda']['host'],
        'port' => (string) $dbConfigs['tienda']['port'],
        'name' => $dbConfigs['tienda']['name'],
        'user' => $dbConfigs['tienda']['user'],
        'password' => $dbConfigs['tienda']['password'],
    ]);

    write_php_config($root.'/acceso/private/config.php', [
        'base_url' => $accesoUrl,
        'store_url' => $storeUrl,
        'liquid_url' => $liquidUrl,
        'require_https' => true,
        'store_db' => $dbConfigs['tienda'],
        'identity_db' => $dbConfigs['identidad'],
        'clients' => [
            'tienda' => [
                'redirect_uri' => $redirectTienda,
                'secret' => $ssoTienda,
            ],
            'liquidaciones' => [
                'redirect_uri' => $redirectLiquidaciones,
                'secret' => $ssoLiquidaciones,
            ],
        ],
    ]);

    write_php_config($root.'/liquidaciones/private/config.php', [
        'db' => $dbConfigs['liquidaciones'],
        'app_key' => $appKey,
        'allow_local_http' => false,
    ]);

    if ($ssoEnabled) {
        write_php_config($root.'/tienda/app/sso.config.php', [
            'enabled' => true,
            'auth_base_url' => $accesoUrl,
            'client_id' => 'tienda',
            'client_secret' => $ssoTienda,
            'redirect_uri' => $redirectTienda,
        ]);
        write_php_config($root.'/liquidaciones/private/sso.config.php', [
            'enabled' => true,
            'auth_base_url' => $accesoUrl,
            'client_id' => 'liquidaciones',
            'client_secret' => $ssoLiquidaciones,
            'redirect_uri' => $redirectLiquidaciones,
        ]);
    } else {
        remove_php_config($root.'/tienda/app/sso.config.php');
        remove_php_config($root.'/liquidaciones/private/sso.config.php');
        out('SSO desactivado para tienda/liquidaciones (login local).');
    }

    if (env_bool('MPI_RUN_MIGRATIONS', true)) {
        $migrationsDir = $root.'/docker/migrations';
        $tiendaBaseline = ['001_tienda_schema' => $root.'/tienda/schema.sql'];
        $identidadBaseline = ['002_identidad_schema' => $root.'/acceso/schema.sql'];
        $liquidacionesBaseline = [
            '003_liquidaciones_schema' => $root.'/liquidaciones/private/schema.sql',
            '004_liquidaciones_identity_links' => $root.'/liquidaciones/private/identity_links.sql',
        ];

        if ($singleMode) {
            apply_migrations(
                $pdos['tienda'],
                'base única',
                array_merge($tiendaBaseline, $identidadBaseline, $liquidacionesBaseline),
                ['tienda', 'identidad', 'liquidaciones', 'all'],
                $migrationsDir
            );
            // Los tres esquemas viven en la misma base: reutilizamos una conexión.
            $pdos['identidad'] = $pdos['tienda'];
            $pdos['liquidaciones'] = $pdos['tienda'];
        } else {
            apply_migrations($pdos['tienda'], 'tienda', $tiendaBaseline, ['tienda', 'all'], $migrationsDir);
            apply_migrations($pdos['identidad'], 'identidad', $identidadBaseline, ['identidad', 'all'], $migrationsDir);
            apply_migrations($pdos['liquidaciones'], 'liquidaciones', $liquidacionesBaseline, ['liquidaciones', 'all'], $migrationsDir);
        }
    } else {
        out('MPI_RUN_MIGRATIONS=0: se omite la aplicación de esquemas.');
    }

    // Serializa la creación del admin y la carga DEMO entre varios contenedores
    // (útil para la variante de tres servicios). Es idempotente.
    $seedLockName = 'mpi_seed_data';
    $seedLock = $pdos['tienda']->prepare('SELECT GET_LOCK(?, 60)');
    $seedLock->execute([$seedLockName]);
    if ((int) $seedLock->fetchColumn() !== 1) {
        fail('No se pudo obtener el bloqueo de inicialización de datos.');
    }

    try {
        $admin = ensure_admin($pdos['tienda'], $domainTienda);

        if ($admin !== null && $admin['created']) {
            $grant = $pdos['identidad']->prepare(
                "INSERT INTO identity_grants (store_user_id, liquid_role, enabled) VALUES (?, 'admin', 1)
                 ON DUPLICATE KEY UPDATE liquid_role=VALUES(liquid_role), enabled=VALUES(enabled)"
            );
            $grant->execute([$admin['id']]);
            out('Permiso de liquidaciones concedido al administrador inicial.');
            ensure_liquidaciones_admin($pdos['liquidaciones'], $admin['username'], $admin['password']);
        } elseif ($admin !== null) {
            // Si se proporcionó la contraseña correcta, deja un acceso local de respaldo
            // en liquidaciones para cuando SSO esté desactivado.
            $providedPassword = trim(env_raw('MPI_ADMIN_PASSWORD', '') ?? '');
            if ($providedPassword !== '') {
                $hash = $pdos['tienda']->prepare('SELECT password_hash FROM admin_users WHERE id = ? LIMIT 1');
                $hash->execute([$admin['id']]);
                $passwordHash = $hash->fetchColumn();
                if (is_string($passwordHash) && password_verify($providedPassword, $passwordHash)) {
                    ensure_liquidaciones_admin($pdos['liquidaciones'], $admin['username'], $providedPassword);
                }
            }
        }

        if (env_bool('MPI_SEED_DEMO', true)) {
            seed_liquidaciones($pdos['liquidaciones'], true);
            if ($admin !== null) {
                seed_tienda_demo($pdos['tienda'], $root.'/tienda/fixtures/demo_catalog.json', $admin['id']);
            } else {
                out('Aviso: sin administrador no se carga el contenido DEMO de la tienda.');
            }
        } else {
            seed_liquidaciones($pdos['liquidaciones'], false);
            out('MPI_SEED_DEMO=0: se omite el catálogo DEMO.');
        }
    } finally {
        $releaseSeed = $pdos['tienda']->prepare('SELECT RELEASE_LOCK(?)');
        $releaseSeed->execute([$seedLockName]);
    }

    out('Inicialización completada.');
}

try {
    main();
} catch (Throwable $e) {
    fail($e->getMessage());
}
