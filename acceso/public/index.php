<?php
declare(strict_types=1);
require dirname(__DIR__).'/private/bootstrap.php';id_secure();id_session();$err='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 id_check_csrf();
 $name=strtolower(trim((string)($_POST['identifier']??'')));$pass=(string)($_POST['password']??'');$ip=hash('sha256',(string)($_SERVER['REMOTE_ADDR']??'unknown'));
 try{
  if($name===''||strlen($name)>190||strlen($pass)>1024)throw new RuntimeException('Datos de acceso inválidos.');
  $db=id_db_identity();$db->beginTransaction();
  $db->prepare('INSERT IGNORE INTO identity_login_limits(ip_hash,attempts,window_started) VALUES (?,0,UTC_TIMESTAMP())')->execute([$ip]);
  $q=$db->prepare('SELECT attempts,window_started FROM identity_login_limits WHERE ip_hash=? FOR UPDATE');$q->execute([$ip]);$row=$q->fetch();
  $age=time()-strtotime($row['window_started'].' UTC');$attempts=$age>=900?0:(int)$row['attempts'];
  if($attempts>=5){$db->commit();http_response_code(429);header('Retry-After: 900');throw new RuntimeException('Demasiados intentos. Intenta más tarde.');}
  $q=id_store()->prepare('SELECT id,email,username,password_hash,role,active,auth_version FROM admin_users WHERE (email=? OR username=?) LIMIT 1');$q->execute([$name,$name]);$u=$q->fetch();
  $ok=$u&&(int)$u['active']===1&&password_verify($pass,$u['password_hash']);
  if(!$ok){$db->prepare('UPDATE identity_login_limits SET attempts=?,window_started=IF(?=0,UTC_TIMESTAMP(),window_started) WHERE ip_hash=?')->execute([$attempts+1,$attempts,$ip]);$db->commit();throw new RuntimeException('Usuario o contraseña incorrectos.');}
  $db->prepare('UPDATE identity_login_limits SET attempts=0,window_started=UTC_TIMESTAMP() WHERE ip_hash=?')->execute([$ip]);
  $sid=bin2hex(random_bytes(32));
  $db->prepare('INSERT INTO identity_sessions(session_id,store_user_id,store_auth_version,created_at,last_seen_at,expires_at) VALUES (?,?,?,UTC_TIMESTAMP(),UTC_TIMESTAMP(),DATE_ADD(UTC_TIMESTAMP(), INTERVAL 12 HOUR))')->execute([$sid,(int)$u['id'],(int)$u['auth_version']]);
  $db->commit();session_regenerate_id(true);$_SESSION['identity']=['id'=>(int)$u['id'],'ver'=>(int)$u['auth_version'],'sid'=>$sid];
  $target=$_SESSION['pending_authorize']??'/index.php';unset($_SESSION['pending_authorize']);
  header('Location: '.$target,true,303);exit;
 }catch(Throwable $e){if(isset($db)&&$db instanceof PDO&&$db->inTransaction())$db->rollBack();$err=$e instanceof RuntimeException?$e->getMessage():'No fue posible validar el acceso. Revisa la conexión.';error_log('Identity sign-in: '.get_class($e));}
}
$u=id_user();id_body($u?'Centro de operaciones':'Acceso central');
echo '<div class="mpi-sso-brand"><img src="/assets/mpi_logo_megaprime_importaciones.png" alt="Mega Prime Importaciones" width="72" height="72"><h1>MEGA PRIME IMPORT · Acceso central</h1></div>';
if($err)echo '<p class="error" role="alert">'.id_escape($err).'</p>';
if(!$u){?>
<p>Una sola cuenta para administrar la tienda y consultar las liquidaciones autorizadas. Los visitantes de la tienda no necesitan iniciar sesión.</p>
<form method="post"><input type="hidden" name="csrf" value="<?=id_escape(id_csrf())?>"><label>Usuario o correo<input name="identifier" autocomplete="username" required maxlength="190"></label><label>Contraseña<input type="password" name="password" autocomplete="current-password" required></label><button>Ingresar</button></form>
<p><a href="<?=id_escape(rtrim(id_cfg()['store_url'],'/').'/admin/recover.php')?>">Recuperar mi contraseña</a></p>
<?php }else{
echo '<p>Sesión: <b>'.id_escape($u['username']).'</b> · '.id_escape($u['email']).'</p><div class="cards">';
echo '<div class="card"><h2>Tienda y administración</h2><p>Productos, categorías, contenidos y cotizaciones.</p><a class="btn" href="'.id_escape(rtrim(id_cfg()['store_url'],'/').'/admin/').'">Abrir tienda</a></div>';
if(id_grant((int)$u['id'],'liquidaciones'))echo '<div class="card"><h2>Liquidaciones</h2><p>Importaciones, tributos, gastos y rentabilidad.</p><a class="btn" href="'.id_escape(rtrim(id_cfg()['liquid_url'],'/').'/admin.php').'">Abrir liquidaciones</a></div>';
else echo '<div class="card"><h2>Liquidaciones</h2><p>No tienes permiso asignado. Solicítalo al administrador.</p></div>';
echo '</div><nav style="margin-top:20px">';if($u['role']==='admin')echo '<a href="/permissions.php">Gestionar permisos de liquidación</a>';
echo '<a href="'.id_escape(rtrim(id_cfg()['store_url'],'/').'/admin/account.php').'">Mi cuenta</a><a href="/logout.php">Cerrar sesión global</a></nav>';
}
id_end();
