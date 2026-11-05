<?php
declare(strict_types=1);
require dirname(__DIR__).'/private/bootstrap.php';
mpi_require_transport();mpi_headers(true);mpi_session();
if(mpi_sso_enabled()){
  if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='logout'){
    mpi_check_csrf();$identity=$_SESSION['sso_identity']??[];
    $revoked=is_array($identity)?mpi_sso_revoke($identity):false;
    mpi_logout();mpi_redirect(rtrim(mpi_sso_config()['auth_base_url'],'/').($revoked?'/index.php?logged_out=1':'/logout.php'));
  }
  $signed=mpi_user();
  if(!$signed){header('Location: '.mpi_sso_authorize_url(),true,303);exit;}
  define('MPI_AUTHORIZED',true);require dirname(__DIR__).'/private/calculadora.php';exit;
}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 mpi_check_csrf();
 if(($_POST['action']??'')==='logout'){
  mpi_logout();
  mpi_redirect('admin.php');
 }
 $user=mb_safe(trim((string)($_POST['username']??'')),100);
 $password=(string)($_POST['password']??'');
 if(strlen($password)>1024) $password='';
 $ip=mpi_client_hash();$pdo=mpi_db();
 $pdo->beginTransaction();
 try {
  $pdo->prepare('INSERT IGNORE INTO login_attempts(ip_hash,attempts,window_start) VALUES (?,0,NOW())')->execute([$ip]);
  $s=$pdo->prepare('SELECT attempts,TIMESTAMPDIFF(SECOND,window_start,NOW()) AS age_seconds FROM login_attempts WHERE ip_hash=? FOR UPDATE');$s->execute([$ip]);$r=$s->fetch();
  $age=(int)$r['age_seconds'];$attempts=(int)$r['attempts'];
  if($age>=900){$attempts=0;$pdo->prepare('UPDATE login_attempts SET attempts=0,window_start=NOW() WHERE ip_hash=?')->execute([$ip]);}
  if($attempts>=5){http_response_code(429);header('Retry-After: 900');$error='Demasiados intentos. Vuelve a intentar más tarde.';}
  else {
   $s=$pdo->prepare('SELECT id,password_hash,active,role,credential_version FROM users WHERE username=?');$s->execute([$user]);$found=$s->fetch();
   $good=$found&&(int)$found['active']===1&&password_verify($password,$found['password_hash']);
   if($good){$pdo->prepare('UPDATE login_attempts SET attempts=0,window_start=NOW() WHERE ip_hash=?')->execute([$ip]);}
   else{$pdo->prepare('UPDATE login_attempts SET attempts=attempts+1 WHERE ip_hash=?')->execute([$ip]);$error='Usuario o contraseña incorrectos.';}
  }
  $pdo->commit();
  if(!empty($good)){
   session_regenerate_id(true);
   $_SESSION=['uid'=>(int)$found['id'],'ver'=>(string)$found['credential_version'],'last'=>time(),'csrf'=>bin2hex(random_bytes(32))];
   mpi_audit((int)$found['id'],'login');
   mpi_redirect($found['role']==='editor'?'panel.php':'admin.php');
  }
 } catch (Throwable $e){if($pdo->inTransaction())$pdo->rollBack();error_log('MPI Login: '.$e->getMessage());http_response_code(503);$error='Inicio de sesión temporalmente no disponible.';}
}
$u=mpi_user();
if($u){
 if($u['role']==='editor')mpi_redirect('panel.php');
 define('MPI_AUTHORIZED',true);
 require dirname(__DIR__).'/private/calculadora.php';exit;
}
?>
<!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"><title>Acceso · Mega Prime Import</title><link rel="stylesheet" href="assets/store.css"></head>
<body class="login-body"><main class="login-card"><a class="brand" href="index.php">MEGA PRIME IMPORT</a><h1>Acceso privado</h1><p>Ingresa con tu cuenta individual para gestionar el catálogo o utilizar la calculadora.</p>
<?php if($error):?><p class="notice error" role="alert"><?=mpi_e($error)?></p><?php endif;?>
<form method="post" action="admin.php"><input type="hidden" name="_csrf" value="<?=mpi_e(mpi_csrf())?>"><label>Usuario<input name="username" required maxlength="100" autocomplete="username"></label><label>Contraseña<input type="password" name="password" required autocomplete="current-password" maxlength="1024"></label><button class="btn gold" type="submit">Ingresar</button></form><p class="muted">Si olvidaste la contraseña, solicita al administrador que la restablezca. No existen cuentas de demostración.</p></main></body></html>
