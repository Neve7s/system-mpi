<?php
declare(strict_types=1);
// Configuración compartida: archivos privados FUERA del DocumentRoot.
define('MPI_ROOT', dirname(__DIR__));
require_once __DIR__.'/sso_client.php';
function mpi_config(): array {
    static $config = null;
    if ($config === null) {
        $file = MPI_ROOT.'/private/config.php';
        if (!is_file($file)) {http_response_code(503);exit('Instalación pendiente. Consulte la guía de despliegue V85.');}
        $config = require $file;
        if (!is_array($config) || !isset($config['db'], $config['app_key'])) {http_response_code(503);exit('Configuración privada incompleta.');}
    }
    return $config;
}
function mpi_db(): PDO {
    static $pdo = null;
    if (!$pdo) {
        $d = mpi_config()['db'];
        try {
            $pdo = new PDO('mysql:host='.$d['host'].';port='.(int)($d['port']??3306).';dbname='.$d['name'].';charset=utf8mb4',
                $d['user'],$d['password'],[
                PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES=>false,
                PDO::ATTR_TIMEOUT=>4
            ]);
        } catch (Throwable $e) {error_log('MPI DB connection failed: '.$e->getMessage());http_response_code(503);exit('Base de datos no disponible. Compruebe la configuración del servidor.');}
    }
    return $pdo;
}
function mpi_https(): bool {
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ((string)($_SERVER['SERVER_PORT']??'') === '443');
}
function mpi_require_transport(): void {
    $c=mpi_config();
    $local=in_array($_SERVER['REMOTE_ADDR']??'', ['127.0.0.1','::1'],true);
    if (!mpi_https() && !($local && ($c['allow_local_http']??false))) {http_response_code(403);exit('Se requiere HTTPS para usar este sitio.');}
}
function mpi_headers(bool $private=false): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
    header("Content-Security-Policy: base-uri 'self'; object-src 'none'; frame-ancestors 'none'");
    if (mpi_https()) header('Strict-Transport-Security: max-age=31536000');
    if ($private) {header('Cache-Control: no-store, private, max-age=0');header('X-Robots-Tag: noindex, nofollow, noarchive');}
}
function mpi_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');ini_set('session.cookie_httponly','1');
    session_name('MPI_V84_SES');
    session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>mpi_https(),'httponly'=>true,'samesite'=>'Lax']);
    session_start();
    $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}
function mpi_logout(): void {
    mpi_session();
    $_SESSION=[];
    if (ini_get('session.use_cookies')) {
        $params=session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires'=>time()-42000,
            'path'=>$params['path'] ?: '/',
            'domain'=>$params['domain'] ?: '',
            'secure'=>(bool)$params['secure'],
            'httponly'=>true,
            'samesite'=>$params['samesite'] ?: 'Lax'
        ]);
    }
    session_destroy();
}
function mpi_csrf(): string {mpi_session();return (string)$_SESSION['csrf'];}
function mpi_check_csrf(): void {
    mpi_session();
    $val=$_POST['_csrf']??$_SERVER['HTTP_X_CSRF_TOKEN']??'';
    if (!is_string($val)||!hash_equals(mpi_csrf(),$val)){http_response_code(403);exit('Validación de sesión fallida (CSRF).');}
    // Reject cross-origin POST (Origin optional for older browsers)
    $origin=$_SERVER['HTTP_ORIGIN']??'';
    if ($origin!=='' && strtolower((string)$origin)!=='null' && (parse_url($origin,PHP_URL_HOST) !== (explode(':',(string)($_SERVER['HTTP_HOST']??'--'))[0]))) {http_response_code(403);exit('Origen no permitido.');}
}
function mpi_e(?string $v): string {return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function mpi_redirect(string $url): never {header('Location: '.$url,true,303);exit;}
function mpi_user(): ?array {
    mpi_session();
    if (empty($_SESSION['uid']) || time()-(int)($_SESSION['last']??0)>1800){unset($_SESSION['uid'],$_SESSION['ver']);return null;}
    $s=mpi_db()->prepare('SELECT id,username,display_name,role,active,credential_version FROM users WHERE id=?');
    $s->execute([(int)$_SESSION['uid']]);$u=$s->fetch();
    if (!$u || !(int)$u['active'] || (string)$u['credential_version'] !== (string)($_SESSION['ver']??'')){
        unset($_SESSION['uid'],$_SESSION['ver']);return null;
    }
    if(mpi_sso_enabled()){
        $identity=$_SESSION['sso_identity']??null;
        $proof=is_array($identity)?mpi_sso_status($identity):null;
        if(!$proof||!in_array($proof['role']??'', ['admin','analyst'],true)){
            unset($_SESSION['uid'],$_SESSION['ver'],$_SESSION['sso_identity']);return null;
        }
        $q=mpi_db()->prepare('SELECT local_user_id FROM identity_user_links WHERE store_user_id=?');$q->execute([(int)$proof['user_id']]);
        if((int)$q->fetchColumn()!==(int)$u['id']||$u['role']!==$proof['role']){
            unset($_SESSION['uid'],$_SESSION['ver'],$_SESSION['sso_identity']);return null;
        }
    }
    $_SESSION['last']=time();return $u;
}
function mpi_require_user(array $roles=[]): array {
    $u=mpi_user();
    if (!$u){
      if(mpi_sso_enabled()){
       mpi_session();header('Location: '.mpi_sso_authorize_url(),true,303);exit;
      }
      mpi_redirect('admin.php');
    }
    if ($roles && !in_array($u['role'],$roles,true)){http_response_code(403);exit('Acceso denegado: permiso insuficiente.');}
    return $u;
}
function mpi_audit(?int $user,string $action,string $target=''): void {
    $s=mpi_db()->prepare('INSERT INTO audit_events (user_id,action_name,target) VALUES (?,?,?)');
    $s->execute([$user,mb_safe($action,100),mb_safe($target,130)]);
}
function mb_safe(string $value,int $size):string{return function_exists('mb_substr')?mb_substr($value,0,$size):substr($value,0,$size);}
function mpi_setting(string $key,string $default=''):string {
    static $settings = null;
    if ($settings===null) {$settings=[];foreach(mpi_db()->query('SELECT setting_key,setting_value FROM site_settings') as $r)$settings[$r['setting_key']]=$r['setting_value'];}
    return $settings[$key]??$default;
}
function mpi_valid_url(string $url):bool{
    if ($url==='')return true;
    $parts=parse_url($url);
    return is_array($parts)&&($parts['scheme']??'')==='https'&&isset($parts['host'])&& !isset($parts['user'])&&!isset($parts['pass']) && filter_var($url,FILTER_VALIDATE_URL)!==false && !preg_match("~[\\x00-\\x20\\x7F'\"<>`()]~",$url);
}
function mpi_image(string $path):string {
    if ($path==='')return '';
    if (preg_match('~^uploads/[a-f0-9]{32}\.(?:jpg|png|webp)$~',$path))return $path;
    return mpi_valid_url($path)?$path:'';
}
function mpi_client_hash():string {
    // No X-Forwarded-For sin lista de proxies de confianza.
    return hash_hmac('sha256',(string)($_SERVER['REMOTE_ADDR']??'unknown'),mpi_config()['app_key']);
}
