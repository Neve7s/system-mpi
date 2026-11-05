<?php
declare(strict_types=1);
function id_cfg(): array {
 static $cfg=null;
 if($cfg===null){$p=__DIR__.'/config.php';if(!is_file($p)){http_response_code(503);exit('Acceso central pendiente de configuración.');}
 $cfg=require $p;
 if(!is_array($cfg)||empty($cfg['store_db'])||empty($cfg['identity_db'])||empty($cfg['clients'])||empty($cfg['base_url'])||($cfg['require_https']??null)!==true){http_response_code(503);exit('Configuración incompleta o HTTPS deshabilitado.');}
 if(!preg_match('~^https://[a-z0-9.-]+(?::443)?$~i',(string)$cfg['base_url'])){http_response_code(503);exit('URL segura del centro incorrecta.');}
 foreach(['tienda','liquidaciones'] as $key){$c=$cfg['clients'][$key]??[];if(!is_array($c)||!preg_match('~^https://[a-z0-9.-]+(?:/[^\s?#]*)$~i',(string)($c['redirect_uri']??''))||strlen((string)($c['secret']??''))<32||str_contains((string)($c['secret']??''),'REEMPLAZAR')){http_response_code(503);exit('Configura el callback HTTPS y el secreto de cada módulo.');}}}
 return $cfg;
}
function id_secure():void {
 header('Cache-Control: no-store, private, max-age=0');header('X-Content-Type-Options: nosniff');header('X-Frame-Options: DENY');header('Referrer-Policy: no-referrer');header('Permissions-Policy: camera=(), microphone=(), geolocation=()');header('Cross-Origin-Opener-Policy: same-origin');header('X-Robots-Tag: noindex, nofollow');
 header("Content-Security-Policy: default-src 'self'; base-uri 'none'; frame-ancestors 'none'; form-action 'self'; object-src 'none'");
 $isHttps=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')||($_SERVER['SERVER_PORT']??'')==='443';
 if($isHttps)header('Strict-Transport-Security: max-age=31536000');
 if(($cfg=id_cfg())['require_https']&&!$isHttps){http_response_code(403);exit('Se requiere HTTPS.');}
}
function id_db(array $c):PDO {
 return new PDO('mysql:host='.$c['host'].';port='.(int)($c['port']??3306).';dbname='.$c['name'].';charset=utf8mb4',$c['user'],$c['password'],[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>4]);
}
function id_store():PDO {static $p=null;return $p??=id_db(id_cfg()['store_db']);}
function id_db_identity():PDO {static $p=null;return $p??=id_db(id_cfg()['identity_db']);}
function id_session():void {
 if(session_status()===PHP_SESSION_ACTIVE)return;
 ini_set('session.use_strict_mode','1');ini_set('session.use_only_cookies','1');
 session_name('mpi_identity');
 session_set_cookie_params(['lifetime'=>0,'path'=>'/','secure'=>(bool)id_cfg()['require_https'],'httponly'=>true,'samesite'=>'Lax']);
 session_start();
}
function id_csrf():string {id_session();return $_SESSION['csrf']??=bin2hex(random_bytes(32));}
function id_check_csrf():void {
 if(!hash_equals(id_csrf(),(string)($_POST['csrf']??''))){http_response_code(403);exit('Token inválido.');}
 $origin=(string)($_SERVER['HTTP_ORIGIN']??'');
 if($origin!==''){
  $expected=(string)id_cfg()['base_url'];
  $sameScheme=parse_url($origin,PHP_URL_SCHEME)===parse_url($expected,PHP_URL_SCHEME);
  $sameHost=strcasecmp((string)parse_url($origin,PHP_URL_HOST),(string)parse_url($expected,PHP_URL_HOST))===0;
  $originPort=parse_url($origin,PHP_URL_PORT)?:443;$expectedPort=parse_url($expected,PHP_URL_PORT)?:443;
  if(!$sameScheme||!$sameHost||$originPort!==$expectedPort){http_response_code(403);exit('Origen no permitido.');}
 }
}
function id_escape(mixed $s):string{return htmlspecialchars((string)$s,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function id_json(array $r,int $status=200):never {header('Content-Type: application/json; charset=utf-8');http_response_code($status);echo json_encode($r,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);exit;}
function id_get_user(int $uid):?array {
 $q=id_store()->prepare('SELECT id,email,username,password_hash,role,active,auth_version FROM admin_users WHERE id=? LIMIT 1');$q->execute([$uid]);$u=$q->fetch();return $u?:null;
}
function id_user():?array {
 id_session();$i=$_SESSION['identity']??null;
 if(!is_array($i)||empty($i['id'])||empty($i['sid']))return null;
 $u=id_get_user((int)$i['id']);
 if(!$u||(int)$u['active']!==1||(int)$u['auth_version']!==(int)($i['ver']??0)){unset($_SESSION['identity']);return null;}
 $q=id_db_identity()->prepare('SELECT session_id FROM identity_sessions WHERE session_id=? AND store_user_id=? AND revoked_at IS NULL AND expires_at>UTC_TIMESTAMP() AND last_seen_at>DATE_SUB(UTC_TIMESTAMP(), INTERVAL 30 MINUTE) LIMIT 1');
 $q->execute([$i['sid'],(int)$u['id']]);if(!$q->fetchColumn()){unset($_SESSION['identity']);return null;}
 return $u;
}
function id_grant(int $user,string $client):?string {
 if($client==='tienda')return 'store';
 if($client!=='liquidaciones')return null;
 $q=id_db_identity()->prepare('SELECT liquid_role FROM identity_grants WHERE store_user_id=? AND enabled=1');$q->execute([$user]);
 $r=$q->fetchColumn();
 if(!in_array($r,['admin','analyst'],true))return null;
 // Un administrador de liquidaciones debe continuar siendo administrador en la tienda.
 if($r==='admin' && (id_get_user($user)['role']??'')!=='admin')return null;
 return $r;
}
function id_validate_client(string $client,string $redirect):bool {
 $c=id_cfg()['clients'][$client]??null;
 return is_array($c)&&hash_equals((string)$c['redirect_uri'],$redirect);
}
function id_authenticated_client():array {
 $client=(string)($_POST['client_id']??'');$secret=(string)($_POST['client_secret']??'');
 $c=id_cfg()['clients'][$client]??null;
 if(!is_array($c)||!is_string($secret)||strlen($secret)>512||!hash_equals((string)$c['secret'],$secret)){id_json(['error'=>'invalid_client'],401);}
 return ['id'=>$client,'settings'=>$c];
}
function id_session_valid(string $sid,int $uid):?array {
 if(!preg_match('/^[a-f0-9]{64}$/D',$sid)||$uid<1)return null;
 $u=id_get_user($uid);if(!$u||(int)$u['active']!==1)return null;
 $q=id_db_identity()->prepare('SELECT session_id,store_auth_version FROM identity_sessions WHERE session_id=? AND store_user_id=? AND revoked_at IS NULL AND expires_at>UTC_TIMESTAMP() AND last_seen_at>DATE_SUB(UTC_TIMESTAMP(),INTERVAL 30 MINUTE)');
 $q->execute([$sid,$uid]);$s=$q->fetch();
 if(!$s||(int)$s['store_auth_version']!==(int)$u['auth_version'])return null;
 return $u;
}
function id_touch(string $sid):void {id_db_identity()->prepare('UPDATE identity_sessions SET last_seen_at=UTC_TIMESTAMP() WHERE session_id=? AND revoked_at IS NULL')->execute([$sid]);}
function id_body(string $title):void {echo '<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>'.id_escape($title).' · MPI</title><link rel="stylesheet" href="/identity.css"></head><body><main>'; }
function id_end():void {echo '</main></body></html>';}
