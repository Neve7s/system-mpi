# Despliegue con Docker, Coolify y Cloudflare

Esta guía sustituye la instalación manual para el despliegue real. El proyecto se empaqueta en **una imagen Docker** que sirve los tres sitios con tres `VirtualHost` de Apache.

## 1. Qué se despliega

| Sitio | Variable de dominio | DocumentRoot | Base por defecto |
|---|---|---|---|
| Tienda + CMS | `MPI_DOMAIN_TIENDA` | `tienda/public` | `mpi_tienda` |
| Centro SSO | `MPI_DOMAIN_ACCESO` | `acceso/public` | `mpi_identidad` |
| Liquidaciones | `MPI_DOMAIN_LIQUIDACIONES` | `liquidaciones/public` | `mpi_liquidaciones` |

La recomendación es **un solo servicio en Coolify con tres dominios**. También existe `docker-compose.three-services.yml` si tu versión de Coolify solo permite un dominio por servicio.

```
                    ┌─────────────────────────────┐
Cloudflare DNS ───▶ │ Coolify / Traefik (443 TLS) │
                    └──────────────┬──────────────┘
                                   │ HTTP :80 + Host
                    ┌──────────────▼──────────────┐
                    │ Contenedor mpi-ecuador      │
                    │  Apache + PHP 8.3           │
                    │  ├─ tienda/public           │
                    │  ├─ acceso/public           │
                    │  └─ liquidaciones/public    │
                    └──────────────┬──────────────┘
                                   │ MARIADB_URL
                    ┌──────────────▼──────────────┐
                    │ MariaDB existente            │
                    │ 3 bases o 1 base (auto)      │
                    └─────────────────────────────┘
```

## 2. Requisitos

- VPS con Coolify funcionando y los puertos **80** y **443** abiertos.
- MariaDB 10.5+ / MySQL 8+ accesible desde el contenedor.
- Un repositorio en GitHub con este proyecto.
- Zona DNS en Cloudflare para el dominio.
- `MARIADB_URL` de la base existente.

Extensiones y configuración ya vienen en la imagen: PHP 8.3, `pdo_mysql`, `curl`, `openssl`, `fileinfo`, `mbstring`, `json`, Apache con `mod_headers`, `mod_remoteip`, `mod_deflate` y `mod_rewrite`.

## 3. Variables de entorno

Configúralas en **Coolify → recurso → Environment Variables**. No subas secretos a Git.

### Obligatorias

| Variable | Ejemplo | Notas |
|---|---|---|
| `MARIADB_URL` | `mysql://usuario:clave@mariadb:3306/mpi` | Acepta `mysql://` o `mariadb://`. Si la clave tiene caracteres especiales, codifícala (`@` → `%40`). |

### Dominios

| Variable | Ejemplo |
|---|---|
| `MPI_DOMAIN_TIENDA` | `megaprimeimport.com` |
| `MPI_DOMAIN_ACCESO` | `acceso.megaprimeimport.com` |
| `MPI_DOMAIN_LIQUIDACIONES` | `liquidaciones.megaprimeimport.com` |

Sin `https://`, sin barra final y sin puerto. Deben coincidir **exactamente** con los dominios de Coolify y los registros DNS. El contenedor deriva de aquí los callbacks SSO (`https://tienda/admin/sso_callback.php` y `https://liquidaciones/sso_callback.php`).

### Bases de datos

| Variable | Por defecto | Cuándo usarla |
|---|---|---|
| `MPI_DB_MODE` | `auto` | `auto` intenta tres bases y cae a una si no hay permisos. `separate` exige tres. `single` usa una sola. |
| `MPI_DB_PREFIX` | `mpi` | Genera `mpi_tienda`, `mpi_identidad`, `mpi_liquidaciones`. |
| `MPI_DB_TIENDA` | `<prefijo>_tienda` | Nombre explícito. |
| `MPI_DB_IDENTIDAD` | `<prefijo>_identidad` | Nombre explícito. |
| `MPI_DB_LIQUIDACIONES` | `<prefijo>_liquidaciones` | Nombre explícito. |
| `MPI_DB_USER_*` | usuario de `MARIADB_URL` | Credenciales separadas por base (opcional). |
| `MPI_DB_PASSWORD_*` | clave de `MARIADB_URL` | Credenciales separadas por base (opcional). |
| `MPI_DB_CONNECT_RETRIES` | `30` | Reintentos de conexión al arrancar (2 s entre intentos). |

### Primer administrador y DEMO

| Variable | Por defecto | Notas |
|---|---|---|
| `MPI_ADMIN_EMAIL` | `admin@<dominio-tienda>` | Correo del primer administrador. |
| `MPI_ADMIN_USERNAME` | `admin` | 4–60 caracteres: `a-z 0-9 . _ -`. |
| `MPI_ADMIN_PASSWORD` | generada | Mínimo 12 caracteres. Si se omite, se guarda en `/data/initial_admin.txt`. |
| `MPI_SEED_ADMIN` | `1` | Crea el admin solo si `admin_users` está vacía. |
| `MPI_SEED_DEMO` | `1` | Carga 27 productos DEMO solo si la base está vacía. |
| `MP_ALLOW_DEMO_PUBLIC` | igual a `MPI_SEED_DEMO` | Permite que la API pública sirva el contenido DEMO. |
| `MPI_RUN_MIGRATIONS` | `1` | Aplica esquemas y migraciones en cada arranque. |

### SSO y recuperación

| Variable | Por defecto | Notas |
|---|---|---|
| `MPI_ENABLE_SSO` | `1` | `0` deja login local en tienda y liquidaciones. |
| `MPI_APP_KEY` | generada en `/data/app_key` | Clave estable para sesiones/IP y para derivar secretos SSO. |
| `MPI_SSO_SECRET_TIENDA` | derivado | Opcional; >= 32 caracteres. |
| `MPI_SSO_SECRET_LIQUIDACIONES` | derivado | Opcional; >= 32 caracteres. |
| `MP_PUBLIC_BASE_URL` | `https://<dominio-tienda>` | Para recuperación de contraseña por correo. |
| `MP_RESET_FROM` | vacío | Remitente validado. Requiere MTA en el host. |

> Si defines `MPI_APP_KEY`, los secretos SSO se derivan de ella. Si no, se genera una vez y se guarda en el volumen `mpi_data`. **No borres ese volumen** salvo que quieras invalidar sesiones y secretos SSO.

## 4. Subir el proyecto a GitHub

Desde la raíz del proyecto:

```sh
git init
git add .
git commit -m "chore: despliegue Docker/Coolify con migraciones automáticas"
git branch -M main
git remote add origin https://github.com/TU_USUARIO/TU_REPO.git
git push -u origin main
```

Antes del primer commit verifica que no se haya colado ningún secreto:

```sh
git status
git diff --cached | grep -iE "password|secret|mariadb_url" || true
```

El `.gitignore` ya excluye `config.local.php`, `config.php`, `sso.config.php`, `.env` y las subidas generadas.

## 5. DNS en Cloudflare

Crea estos registros en la zona del dominio:

| Tipo | Nombre | Contenido | Proxy inicial |
|---|---|---|---|
| A | `@` | IP_V4_DEL_VPS | DNS only |
| A | `acceso` | IP_V4_DEL_VPS | DNS only |
| A | `liquidaciones` | IP_V4_DEL_VPS | DNS only |
| AAAA | `@` | IP_V6_DEL_VPS (si existe) | DNS only |
| AAAA | `acceso` | IP_V6_DEL_VPS | DNS only |
| AAAA | `liquidaciones` | IP_V6_DEL_VPS | DNS only |

Recomendación: deja **DNS only (nube gris)** durante el primer despliegue para que Coolify/Traefik emita los certificados Let's Encrypt por HTTP-01 sin interferencia de Cloudflare.

Cuando el certificado ya funcione, puedes activar el proxy (nube naranja):

1. Cloudflare → SSL/TLS → Overview → **Full (strict)**.
2. No uses **Flexible** (provoca bucles de redirección).
3. Si activas **Always Use HTTPS**, verifica que la renovación de Let's Encrypt siga funcionando. Si falla, vuelve temporalmente a DNS only o usa un certificado de origen de Cloudflare.
4. Desactiva Rocket Loader si rompe el JavaScript del CMS.

Comprueba la propagación:

```sh
dig +short megaprimeimport.com
dig +short acceso.megaprimeimport.com
dig +short liquidaciones.megaprimeimport.com
```

Deben devolver la IP del VPS (o las IPs de Cloudflare si ya está proxied).

### ¿Y `www`?

La opción más simple es **no usar `www`**: el dominio canónico es `megaprimeimport.com`. Si quieres `www`, crea un redirect en Cloudflare (`www` → raíz) y no cambies `MPI_DOMAIN_TIENDA`. Si prefieres que `www` sea el canónico, configura `MPI_DOMAIN_TIENDA=www.megaprimeimport.com` y crea el registro correspondiente; el callback SSO se generará con `www`.

No mezcles ambos: un callback generado para `https://megaprimeimport.com` no coincide con una sesión abierta en `https://www.megaprimeimport.com`.

## 6. Crear el recurso en Coolify

1. Entra a Coolify → **Projects** → tu proyecto.
2. **+ New Resource** → **Docker Compose** (o **Public Repository** / **GitHub App**).
3. Selecciona el repositorio y la rama `main`.
4. Archivo Compose: `/docker-compose.yml`.
5. Base Directory: `/`.
6. En **Environment Variables**, añade `MARIADB_URL` y las variables de la sección 3.
7. Pulsa **Deploy** y observa los logs del servicio `web`.

Cuando el contenedor esté arriba:

1. Abre el servicio `web`.
2. En **Domains**, añade los tres dominios separados por coma:

   ```
   https://megaprimeimport.com,https://acceso.megaprimeimport.com,https://liquidaciones.megaprimeimport.com
   ```

3. Puerto del contenedor: **80**.
4. Guarda y vuelve a desplegar si Coolify no aplica las rutas automáticamente.

> Coolify conserva el encabezado `Host` al pasar por Traefik; Apache usa ese encabezado para elegir el `VirtualHost`. No necesitas configurar Apache a mano: el entrypoint renderiza los tres vhosts con los dominios de `MPI_DOMAIN_*`.

### Si Coolify solo permite un dominio por servicio

Usa `docker-compose.three-services.yml`:

1. Cambia el archivo Compose a `/docker-compose.three-services.yml`.
2. Asigna un dominio a cada servicio:
   - `tienda` → `https://megaprimeimport.com`
   - `acceso` → `https://acceso.megaprimeimport.com`
   - `liquidaciones` → `https://liquidaciones.megaprimeimport.com`
3. Puerto 80 en los tres.
4. Los tres comparten el volumen `mpi_data` para que la clave y los secretos SSO sean iguales.

## 7. Conectar MariaDB

### 7.1 URL interna o pública

- Si MariaDB es otro recurso de Coolify, copia su **Connection URL** interna.
- El host interno suele ser algo como `mariadb-xxxx`. El contenedor web debe estar en la **misma red Docker** que MariaDB. En Coolify, abre MariaDB → **Networks** y conecta el servicio web (o ambos recursos al mismo proyecto/red). Si el host interno no resuelve, usa la URL pública con la IP del VPS y el puerto expuesto (menos seguro) o conecta las redes.
- Prueba desde el contenedor:

  ```sh
  docker exec -it <contenedor-web> php -r '
    $u=getenv("MARIADB_URL");
    $p=parse_url($u);
    $dsn="mysql:host=".$p["host"].";port=".($p["port"]??3306).";dbname=".ltrim($p["path"],"/").";charset=utf8mb4";
    new PDO($dsn, urldecode($p["user"]), urldecode($p["pass"]??""));
    echo "MariaDB OK\n";
  '
  ```

### 7.2 Modos de base de datos

El instalador decide así:

- `auto`: intenta crear/acceder a `mpi_tienda`, `mpi_identidad` y `mpi_liquidaciones`. Si el usuario no tiene permiso de `CREATE DATABASE`, usa la base incluida en `MARIADB_URL` para los tres esquemas. Los esquemas no colisionan entre sí, así que una sola base funciona.
- `separate`: exige tres bases separadas. Recomendado para producción y para que `pruebas/preparacion_produccion.php` valide la separación.
- `single`: usa una sola base a propósito.

### 7.3 Permisos SQL

Si tu usuario de Coolify solo tiene permisos sobre una base, no hace falta hacer nada: `auto` usará esa base. Si quieres tres bases separadas, con un usuario administrador de MariaDB ejecuta:

```sql
CREATE DATABASE IF NOT EXISTS mpi_tienda       CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS mpi_identidad    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE IF NOT EXISTS mpi_liquidaciones CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Con un solo usuario para las tres (lo más simple):

```sql
CREATE USER IF NOT EXISTS 'mpi_app'@'%' IDENTIFIED BY 'UNA_CLAVE_LARGA';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES
  ON mpi_tienda.* TO 'mpi_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES
  ON mpi_identidad.* TO 'mpi_app'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES
  ON mpi_liquidaciones.* TO 'mpi_app'@'%';
FLUSH PRIVILEGES;
```

Luego usa:

```
MARIADB_URL=mysql://mpi_app:UNA_CLAVE_LARGA@host:3306/mpi_tienda
MPI_DB_MODE=separate
```

Con usuarios separados por base (mínimo privilegio), define además `MPI_DB_USER_TIENDA`, `MPI_DB_USER_IDENTIDAD`, `MPI_DB_USER_LIQUIDACIONES` y sus contraseñas. El usuario de `MARIADB_URL` se usa para la conexión inicial al servidor; debe poder ver/crear las tres bases.

> No expongas MariaDB a Internet. Si usas la URL pública para la primera prueba, cierra el puerto después.

## 8. Primer despliegue y primer acceso

En los logs de Coolify deberías ver algo así:

```
[mpi] Dominios: tienda=... acceso=... liquidaciones=...
[mpi] Modo de base de datos solicitado: auto
[mpi] Bases resueltas: {"tienda":"mpi_tienda",...}
[mpi] Aplicando 001_tienda_schema en tienda
[mpi] Aplicando 002_identidad_schema en identidad
[mpi] Aplicando 003_liquidaciones_schema en liquidaciones
[mpi] Aplicando 004_liquidaciones_identity_links en liquidaciones
[mpi] Administrador inicial creado: admin <...>
[mpi] Contraseña inicial generada en /data/initial_admin.txt
[mpi] Permiso de liquidaciones concedido al administrador inicial.
[mpi] Contenido DEMO de la tienda cargado (revisión 1).
[mpi] Catálogo DEMO de liquidaciones cargado.
[mpi] Inicialización completada.
```

Si ves `[mpi][ERROR]`, el contenedor se detiene y Coolify marca el despliegue como fallido. Corrige la variable o el permiso indicado y vuelve a desplegar.

### Obtener la contraseña inicial

Si **no** definiste `MPI_ADMIN_PASSWORD`:

1. Abre la terminal del contenedor `web` en Coolify.
2. Ejecuta:

   ```sh
   cat /data/initial_admin.txt
   ```

   O desde el VPS:

   ```sh
   docker ps
   docker exec -it <contenedor-web> cat /data/initial_admin.txt
   ```

3. Cambia la contraseña al entrar (tienda → **Mi cuenta**).

Si definiste `MPI_ADMIN_PASSWORD`, usa esa contraseña.

### Iniciar sesión con SSO (recomendado, `MPI_ENABLE_SSO=1`)

1. Abre `https://<dominio-tienda>/admin/`.
2. Te redirige al centro de acceso (`https://<dominio-acceso>/`).
3. Inicia sesión con el usuario/correo y la contraseña inicial.
4. Vuelves al editor de la tienda.
5. Abre `https://<dominio-acceso>/`: verás el centro de operaciones.
6. La tarjeta **Liquidaciones** ya está disponible porque el primer administrador recibe permiso `admin`.
7. Abre `https://<dominio-liquidaciones>/admin.php`: el SSO crea el usuario local vinculado y entra a la calculadora.

Para crear más usuarios:

1. Tienda → Administración → **Usuarios** (`/admin/users.php`).
2. Crea el usuario y su rol (`admin` o `editor`).
3. En `https://<dominio-acceso>/permissions.php`, concede permiso de liquidaciones (`analyst` o `admin`) y confirma con tu contraseña de administrador.

Reglas de roles:

| Cuenta de tienda | Permiso financiero | Tienda | Liquidaciones |
|---|---|---|---|
| `admin` | `admin` | Completa | Completa |
| `admin` | sin permiso | Completa | Denegada |
| `editor` | `analyst` | Edición comercial | Liquidación y copias |
| `editor` | sin permiso | Edición comercial | Denegada |

### Iniciar sesión sin SSO (`MPI_ENABLE_SSO=0`)

- Tienda: `https://<dominio-tienda>/admin/`.
- Liquidaciones: `https://<dominio-liquidaciones>/admin.php`.
- El primer arranque crea un administrador local en liquidaciones con las mismas credenciales del administrador de tienda, para que no te quedes fuera.
- `acceso` sigue funcionando como centro independiente, pero no se usa para entrar a los otros dos.

## 9. Verificación posterior al despliegue

### 9.1 URLs

```sh
curl -I https://megaprimeimport.com/
curl -I https://megaprimeimport.com/admin/
curl -I https://acceso.megaprimeimport.com/
curl -I https://liquidaciones.megaprimeimport.com/admin.php
```

Esperado:

- Tienda `/` → `200`.
- Tienda `/admin/` → `303` hacia `acceso` (SSO) o `200` con el formulario local (SSO desactivado).
- Acceso `/` → `200`.
- Liquidaciones `/admin.php` → `303` hacia `acceso` (SSO) o `200` con el login local.

### 9.2 Contenido CMS

```sh
curl -s https://megaprimeimport.com/api/site.php | head -c 400
```

Debe devolver `{"config":{...},"revision":1,...}`. Si devuelve `"config":null` y `MPI_SEED_DEMO=1`, revisa `MP_ALLOW_DEMO_PUBLIC`.

### 9.3 Diagnósticos dentro del contenedor

```sh
docker exec -it <contenedor-web> php /var/www/mpi/pruebas/preparacion_produccion.php
docker exec -it <contenedor-web> php /var/www/mpi/acceso/cli/preflight.php
docker exec -it <contenedor-web> php /var/www/mpi/tienda/cli/preflight.php
docker exec -it <contenedor-web> php /var/www/mpi/liquidaciones/tools/verificar_entorno.php
```

Notas:

- En modo `single`, `preparacion_produccion.php` marca como pendiente “Tres bases con nombres independientes”. Es esperado y el script termina con código 1 por ese único aviso; todo lo demás debe pasar.
- `MP_PUBLIC_BASE_URL` y `MP_RESET_FROM` aparecen pendientes si no configuraste correo saliente. No bloquea el resto.
- `verificar_entorno.php` avisa si `demo_mode=1`. Es correcto mientras estés en DEMO.

### 9.4 Pruebas funcionales mínimas

- [ ] Crear una categoría y un producto en el CMS, subir una foto, guardar y recargar en otro navegador.
- [ ] Confirmar que la foto sigue ahí después de un redeploy.
- [ ] Probar el carrito y la solicitud de proforma en móvil.
- [ ] Probar las calculadoras de teja/PVC/SPC con medidas conocidas.
- [ ] Crear una liquidación de prueba, guardarla, salir y volver a entrar.
- [ ] Exportar Excel y PDF.
- [ ] Quitar un permiso en `acceso/permissions.php` y comprobar que liquidaciones lo deniega en la siguiente petición.
- [ ] Cerrar sesión global y comprobar que tienda y liquidaciones piden login nuevo.

## 10. Actualizaciones y migraciones

### 10.1 Flujo normal

```sh
git add .
git commit -m "feat: ..."
git push origin main
```

En Coolify, activa **Auto Deploy** (webhook de GitHub) o pulsa **Redeploy**. El nuevo contenedor:

1. Se construye desde el nuevo commit.
2. Arranca y ejecuta `docker/bootstrap.php`.
3. Aplica las migraciones pendientes **una sola vez** por base.
4. Regenera la configuración desde las variables de entorno.
5. Arranca Apache y pasa el healthcheck.

Los volúmenes (`mpi_data`, `mpi_uploads_tienda`, `mpi_uploads_liquidaciones`, `mpi_sessions`) se conservan. La base de datos no se toca salvo por las migraciones.

### 10.2 Añadir una migración

Crea un archivo en `docker/migrations/` con este formato:

```
<alcance>__<descripcion>.sql
```

Alcances:

- `tienda__...` → base de tienda.
- `identidad__...` → base de identidad.
- `liquidaciones__...` → base de liquidaciones.
- `all__...` → las tres (o la única en modo single).

Ejemplo:

```sql
-- docker/migrations/tienda__002_add_example_field.sql
ALTER TABLE site_content ADD COLUMN example_field VARCHAR(40) NULL;
```

Reglas:

- No edites una migración ya aplicada: crea una nueva.
- Se registra en la tabla `schema_migrations` de cada base.
- MySQL/MariaDB hace `COMMIT` implícito en DDL. Si una migración falla a la mitad, revisa el estado antes de reintentar. Usa SQL idempotente cuando sea posible.
- El divisor reconoce `;` seguido de salto de línea o fin de archivo. Evita procedimientos con `;` internos.
- Haz un backup antes de `DROP`, `MODIFY` o cambios de tipo.

### 10.3 Rollback

1. Si la migración fue aditiva, vuelve a desplegar el commit anterior en Coolify. El código antiguo sigue funcionando.
2. Si la migración fue destructiva, restaura la base desde el backup y luego vuelve al commit anterior.
3. El runner **no revierte** migraciones automáticamente. No borres filas de `schema_migrations` salvo que sepas exactamente lo que haces.

### 10.4 Si una migración falla

El contenedor no arranca y Coolify muestra el error SQL. Opciones:

- Corregir el SQL y volver a desplegar (si la migración no llegó a aplicarse completa).
- Restaurar el backup de la base y desplegar el commit anterior.
- Como último recurso, `MPI_RUN_MIGRATIONS=0` para arrancar sin migrar; no lo dejes permanente.

## 11. Persistencia, backups y restauración

### 11.1 Volúmenes

| Volumen | Contenido | Qué pasa si se borra |
|---|---|---|
| `mpi_data` | `app_key`, `initial_admin.txt` | Se genera nueva clave; cambian secretos SSO y se invalidan sesiones. El admin sigue en la base. |
| `mpi_uploads_tienda` | Fotos subidas desde el CMS | Se pierden las fotos; el catálogo apunta a archivos inexistentes. |
| `mpi_uploads_liquidaciones` | Imágenes de productos de liquidaciones | Se pierden esas imágenes. |
| `mpi_sessions` | Sesiones PHP | Los usuarios deben volver a iniciar sesión. |

### 11.2 Backup de MariaDB

Localiza el contenedor de MariaDB:

```sh
docker ps --format '{{.Names}}\t{{.Image}}'
```

Backup de las tres bases (modo `separate`):

```sh
docker exec <mariadb> mariadb-dump -uUSUARIO -pCLAVE --single-transaction --routines --triggers mpi_tienda       > mpi_tienda_$(date +%F).sql
docker exec <mariadb> mariadb-dump -uUSUARIO -pCLAVE --single-transaction --routines --triggers mpi_identidad    > mpi_identidad_$(date +%F).sql
docker exec <mariadb> mariadb-dump -uUSUARIO -pCLAVE --single-transaction --routines --triggers mpi_liquidaciones > mpi_liquidaciones_$(date +%F).sql
```

En modo `single`, haz un solo dump de la base indicada en `MARIADB_URL`.

### 11.3 Backup de uploads

```sh
docker volume ls | grep mpi
docker run --rm -v <proyecto>_mpi_uploads_tienda:/data -v "$PWD":/backup alpine \
  tar czf /backup/uploads_tienda_$(date +%F).tar.gz -C /data .
docker run --rm -v <proyecto>_mpi_uploads_liquidaciones:/data -v "$PWD":/backup alpine \
  tar czf /backup/uploads_liquidaciones_$(date +%F).tar.gz -C /data .
```

El nombre exacto del volumen lo añade Coolify; usa `docker volume ls` para confirmarlo.

### 11.4 Restauración

```sh
docker exec -i <mariadb> mariadb -uUSUARIO -pCLAVE mpi_tienda < mpi_tienda_2026-11-05.sql
docker run --rm -v <proyecto>_mpi_uploads_tienda:/data -v "$PWD":/backup alpine \
  sh -c 'cd /data && tar xzf /backup/uploads_tienda_2026-11-05.tar.gz'
```

Prueba la restauración en una base de ensayo antes de confiar en el backup.

## 12. Seguridad y paso a producción

- [ ] `MPI_SEED_DEMO=0` y `MP_ALLOW_DEMO_PUBLIC=0` en producción real.
- [ ] Reemplazar los 27 productos DEMO, precios, fotos, contactos y avisos legales.
- [ ] Cambiar la contraseña inicial del administrador.
- [ ] Usar tres bases separadas y usuarios SQL de mínimo privilegio (`MPI_DB_MODE=separate`).
- [ ] No exponer MariaDB a Internet.
- [ ] Firewall del VPS: solo 22, 80 y 443 (y lo que necesites para administración).
- [ ] Cloudflare en **Full (strict)** si activas el proxy; nunca **Flexible**.
- [ ] HSTS: la app lo envía cuando detecta HTTPS; no lo actives en Cloudflare hasta validar todos los subdominios.
- [ ] Configurar el correo saliente si quieres recuperación por enlace. Sin MTA, usa la recuperación por terminal:
  ```sh
  docker exec -it <contenedor-web> php /var/www/mpi/tienda/cli/admin_recovery.php reset admin
  ```
- [ ] Revisar `tienda/CHECKLIST_PUBLICACION_V93_11.md` y la aceptación interna del proyecto antes de abrir ventas.
- [ ] Configurar backups automáticos de MariaDB y de los volúmenes.
- [ ] Configurar alertas/uptime en Coolify.

## 13. Troubleshooting

| Síntoma | Causa probable | Solución |
|---|---|---|
| `[ERROR] Falta MARIADB_URL` | Variable no definida en Coolify | Añádela al recurso y redeploy. |
| `No se pudo conectar a MariaDB` | Host/puerto/credenciales o red Docker | Revisa la URL, conecta el servicio web a la red de MariaDB y prueba el comando de la sección 7.1. |
| `Access denied for user` | Permisos SQL insuficientes | Concede `SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, REFERENCES` en cada base. |
| `No se pudo crear la base` | Usuario sin `CREATE DATABASE` | Usa `MPI_DB_MODE=single` con una base existente en `MARIADB_URL`, o crea las bases y concede permisos. |
| `503 Acceso central pendiente de configuración` | No se generó `acceso/private/config.php` | Revisa los logs del entrypoint. No arranques el contenedor con `--entrypoint` personalizado. |
| `403 Se requiere HTTPS` | La petición no pasó por Traefik o falta `mpi-proxy.conf` | Accede por el dominio público de Coolify, no por `IP:80`. Revisa que `conf-available/mpi-proxy.conf` esté habilitado. |
| Bucle al iniciar sesión o `Acceso central no disponible` | El contenedor no puede resolver/llamar a `https://acceso...` o ve un certificado distinto | Comprueba `docker exec <web> getent hosts acceso...` y `curl -v https://acceso...` dentro del contenedor. Debe resolver al DNS público y validar el certificado; no mapees el dominio a `host-gateway`. |
| `502 Bad Gateway` | Contenedor caído o sin escuchar en 80 | Revisa logs y healthcheck. |
| `404` en `/admin/` | `DirectoryIndex` antiguo | El repo ya incluye `DirectoryIndex index.html index.php`; asegúrate de desplegar el archivo actualizado. |
| `403` en `/sso_callback.php` de liquidaciones | `.htaccess` sin el callback en la lista blanca | El repo ya lo incluye; redeploy y limpia caché del proxy. |
| Las fotos desaparecen tras un redeploy | Volumen no montado o nombre distinto | Revisa `volumes` y `docker volume ls`; no cambies el nombre del volumen. |
| No se puede subir una imagen | Permisos del volumen | El entrypoint hace `chown`; verifica con `ls -la` dentro del contenedor. |
| Olvidé la contraseña del admin | — | `docker exec -it <contenedor> php /var/www/mpi/tienda/cli/admin_recovery.php reset admin` |
| Una migración falla y el contenedor no arranca | SQL incompatible o parcialmente aplicado | Lee el error, restaura el backup si es necesario, corrige el archivo y redeploy. |
| Error `redirect_uri` / `invalid_client` en SSO | Dominios de Coolify, DNS o `MPI_DOMAIN_*` no coinciden | Deben ser idénticos en los tres sitios. Redeploy para regenerar callbacks. |
| Cloudflare 521/522 | Origen inaccesible | Revisa firewall, puertos 80/443 y que Coolify esté arriba. |
| Let's Encrypt no emite | Proxy de Cloudflare o DNS apuntando a otro sitio | Pon DNS only temporalmente, abre 80/443 y verifica `dig`. |

## 14. Checklist final

- [ ] `MARIADB_URL` configurada y probada.
- [ ] Tres dominios en Coolify y en Cloudflare.
- [ ] Certificados HTTPS válidos en los tres dominios.
- [ ] Logs muestran `Inicialización completada`.
- [ ] Contraseña inicial obtenida y cambiada.
- [ ] SSO probado: admin entra a tienda y liquidaciones.
- [ ] Permisos probados: usuario sin permiso queda denegado.
- [ ] Contenido DEMO reemplazado o decisión consciente de mantenerlo.
- [ ] Backup de base y volúmenes probado con restauración.
- [ ] `preparacion_produccion.php` revisado (y entendido el aviso de modo single).
- [ ] Aviso legal, privacidad, contacto y WhatsApp reales.
- [ ] Ventas reales solo tras completar la aceptación interna y el checklist de publicación.
