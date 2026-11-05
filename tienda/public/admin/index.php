<?php
 declare(strict_types=1);
 require dirname(__DIR__,2).'/app/bootstrap.php';
 sessionStart();
 if(mpi_sso_enabled()){
   if($_SERVER['REQUEST_METHOD']==='POST'&&($_POST['action']??'')==='logout'){
      checkCsrf();$identity=$_SESSION['sso_identity']??[];
      $revoked=is_array($identity)?mpi_sso_revoke($identity):false;
      $_SESSION=[];if(ini_get('session.use_cookies')){$cookie=session_get_cookie_params();setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>$cookie['path']?:'/','secure'=>(bool)$cookie['secure'],'httponly'=>true,'samesite'=>'Lax']);}
      session_destroy();header('Location: '.rtrim(mpi_sso_config()['auth_base_url'],'/').($revoked?'/index.php?logged_out=1':'/logout.php'),true,303);exit;
   }
   if(currentUser()){header('Location: /admin/editor.php',true,303);exit;}
   header('Location: '.mpi_sso_authorize_url(),true,303);exit;
 }
 $error='';
 if($_SERVER['REQUEST_METHOD']==='POST'){
  try{
   checkCsrf();$action=(string)($_POST['action']??'');
   if($action==='logout'){
    // Cierre real: destruir estado del servidor e invalidar la cookie de sesión.
    $_SESSION=[];
    if(ini_get('session.use_cookies')){
     $cookie=session_get_cookie_params();
     setcookie(session_name(),'',[
      'expires'=>time()-3600,
      'path'=>$cookie['path']?:'/',
      'domain'=>$cookie['domain']??'',
      'secure'=>(bool)$cookie['secure'],
      'httponly'=>(bool)$cookie['httponly'],
      'samesite'=>$cookie['samesite']??'Lax'
     ]);
    }
    session_destroy();
    header('Cache-Control: no-store, private');
    header('Location: ./index.php?logged_out=1',true,303);exit;
   }
   if($action==='login'){
    $identifier=trim((string)($_POST['identifier']??''));
    $identifierLower=strtolower($identifier);
    $pass=(string)($_POST['password']??'');
    $ip=(string)($_SERVER['REMOTE_ADDR']??'unknown');
    $conn=db();
    loginGuardAssert($conn,$identifierLower,$ip);
    if($identifierLower===''||strlen($identifierLower)>190||strlen($pass)>1024){loginGuardFailure($conn,$identifierLower,$ip);throw new RuntimeException('Credenciales no válidas');}
    $q=$conn->prepare('SELECT id,email,username,password_hash,role,active,auth_version FROM admin_users WHERE email=? OR username=? LIMIT 1');$q->execute([$identifierLower,$identifierLower]);$u=$q->fetch();
    if(!$u||(int)$u['active']!==1||!password_verify($pass,$u['password_hash'])){
      loginGuardFailure($conn,$identifierLower,$ip);
      throw new RuntimeException('Credenciales no válidas');
    }
    loginGuardSuccess($conn,$identifierLower,$ip);session_regenerate_id(true);
    $_SESSION['auth']=['id'=>(int)$u['id'],'email'=>$u['email'],'username'=>$u['username']?:$u['email'],'role'=>$u['role'],'auth_version'=>(int)$u['auth_version'],'signed_in_at'=>time(),'last_seen_at'=>time()];
    header('Location: ./editor.php');exit;
   }
  }catch(Throwable $e){error_log('Admin login: '.get_class($e));$error=$e instanceof RuntimeException?$e->getMessage():'No fue posible iniciar sesión. Revisa la configuración.';}
 }
 header('Content-Type: text/html; charset=utf-8');header('Cache-Control: no-store');header('X-Frame-Options: DENY');
 $u=currentUser();$tok=csrfToken();
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Mega Prime Import · Acceso administrativo</title>
<style>:root{color-scheme:dark}body{background:linear-gradient(130deg,#080d11,#18252c);color:#f9f4e9;font:16px system-ui,Arial;min-height:100vh;display:grid;place-items:center;margin:0}.card{background:#15232d;max-width:460px;width:calc(100% - 36px);padding:30px;border-radius:18px;border:1px solid #c2a450;box-shadow:0 20px 60px #0008}h1{font-size:23px;color:#e8b94e}.sub{margin-top:-6px;color:#c6d4da}input{box-sizing:border-box;display:block;width:100%;padding:13px;margin:8px 0 20px;border-radius:8px;border:1px solid #71818b;background:#09151c;color:white;font:inherit}button,.btn{background:#e8b94e;color:#111;padding:13px 18px;font:700 15px system-ui;border:0;border-radius:8px;cursor:pointer;text-decoration:none;display:inline-block}label{display:block}p{line-height:1.5;color:#c6d4da}.err{color:#ffbbbb}.ok{color:#bff2d3}.meta{display:grid;gap:8px;margin-top:14px;padding:12px;border:1px solid #35444d;border-radius:12px;background:#101a20}.meta b{color:#f2d07d}</style></head><body><main class="card"><h1>Mega Prime Import · Administración</h1><p class="sub">Acceso con usuario o correo electrónico.</p><?php if(isset($_GET['updated'])):?><p class="ok">Credenciales actualizadas. Ingresa nuevamente.</p><?php endif;?><?php if(isset($_GET['logged_out'])):?><p class="ok">Sesión cerrada correctamente.</p><?php endif;?><?php if($error):?><p class="err"><?=h($error)?></p><?php endif;?><?php if($u):?><p>Sesión: <?=h($u['username']??$u['email'])?> · <?=h($u['email'])?> (<?=h($u['role'])?>)</p><a class="btn" href="./editor.php">Abrir editor</a><form method="post"><input type="hidden" name="csrf" value="<?=h($tok)?>"><input type="hidden" name="action" value="logout"><button>Salir</button></form><?php else:?><p>Acceso protegido para editar la tienda y el catálogo imprimible.</p><form method="post" autocomplete="on"><input type="hidden" name="csrf" value="<?=h($tok)?>"><input type="hidden" name="action" value="login"><label>Usuario o correo<input name="identifier" type="text" autocomplete="username" required maxlength="190"></label><label>Contraseña<input name="password" type="password" autocomplete="current-password" required></label><button>Entrar al administrador</button></form><div class="meta"><div><b>Seguridad del administrador</b></div><div>• Inicio de sesión por usuario o correo.</div><div>• Cambio de usuario, correo y contraseña con verificación de contraseña actual.</div><div>• Recuperación por enlace temporal de un solo uso.</div></div><p><a href="./recover.php" style="color:#f6d27f">¿Olvidaste tu usuario o contraseña?</a></p><?php endif;?></main></body></html>
