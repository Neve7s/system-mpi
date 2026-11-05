<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
header('Cache-Control: no-store, private');header('Referrer-Policy: no-referrer');header('X-Frame-Options: DENY');
sessionStart();$user=currentUser();if(!$user){header('Location: ./index.php');exit;}
$error='';
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  checkCsrf();$password=(string)($_POST['current_password']??'');
  $username=normalizeAdminUsername((string)($_POST['new_username']??''));
  $email=strtolower(trim((string)($_POST['new_email']??'')));
  $newPass=(string)($_POST['new_password']??'');$confirm=(string)($_POST['confirm_password']??'');
  if(!preg_match('/^[a-z0-9._-]{4,60}$/',$username))throw new RuntimeException('El usuario debe tener entre 4 y 60 caracteres y solo usar letras minúsculas, números, punto, guion o guion bajo.');
  if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190)throw new RuntimeException('Indica un correo electrónico válido para el acceso.');
  if(strlen($password)>1024)throw new RuntimeException('Contraseña actual incorrecta.');
  if($newPass!==''&&($newPass!==$confirm||strlen($newPass)<12||strlen($newPass)>1024))throw new RuntimeException('La nueva contraseña debe coincidir y tener al menos 12 caracteres.');
  $pdo=db();$pdo->beginTransaction();
  try{
   $q=$pdo->prepare('SELECT email,username,password_hash,auth_version FROM admin_users WHERE id=? AND active=1 FOR UPDATE');$q->execute([(int)$user['id']]);$row=$q->fetch();
   if(!$row||!password_verify($password,$row['password_hash']))throw new RuntimeException('Contraseña actual incorrecta.');
   if($email===$row['email']&&$username===($row['username']??'')&&$newPass==='')throw new RuntimeException('No hay cambios para guardar.');
   $hash=$newPass!==''?password_hash($newPass,PASSWORD_DEFAULT):$row['password_hash'];
   $q=$pdo->prepare('UPDATE admin_users SET username=?,email=?,password_hash=?,auth_version=auth_version+1 WHERE id=?');$q->execute([$username,$email,$hash,(int)$user['id']]);
   $q=$pdo->prepare('UPDATE admin_password_resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL');$q->execute([(int)$user['id']]);
   $pdo->commit();
  }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
  $_SESSION['auth']=null;unset($_SESSION['auth']);session_regenerate_id(true);
  header('Location: ./index.php?updated=1',true,303);exit;
 }catch(PDOException $e){error_log('Account update: '.$e->getCode());$error='No se pudieron actualizar los datos. Revisa que el usuario o el correo no estén registrados y que la migración V78 esté aplicada.';}
 catch(Throwable $e){$error=$e instanceof RuntimeException?$e->getMessage():'No fue posible guardar los cambios.';}
}
header('Content-Type: text/html; charset=utf-8');
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Seguridad de la cuenta · Mega Prime Import</title>
<style>:root{color-scheme:dark}body{background:#09131a;color:#f7f6f2;font:15px system-ui,Arial;padding:20px}main{max-width:720px;margin:4vh auto;background:#14242d;border:1px solid #b3944e;border-radius:18px;padding:28px}h1{font-size:24px;color:#ebc66e}p{line-height:1.65;color:#cbd9db}.grid{display:grid;grid-template-columns:1fr 1fr;gap:0 18px}label{display:block;margin:14px 0}input{display:block;width:100%;box-sizing:border-box;margin-top:6px;padding:12px;border:1px solid #80929a;border-radius:8px;background:#0b171d;color:white;font:inherit}button,.back{padding:12px 16px;background:#e5bd60;color:#171b1e;font-weight:800;border:0;border-radius:8px;cursor:pointer;text-decoration:none;display:inline-block}.back{background:#273941;color:#f6d794;border:1px solid #8e7541;margin-right:8px}.error{color:#ffbdbd}.tips{margin:16px 0;padding:14px 16px;border:1px solid #40525b;border-radius:12px;background:#0e1a20}.tips strong{color:#f0cb72;display:block;margin-bottom:6px}.tips ul{margin:8px 0 0 18px;padding:0}.tips li{margin:6px 0;color:#d5dde1}@media(max-width:760px){.grid{grid-template-columns:1fr}}</style></head><body><main><h1>Seguridad de mi cuenta</h1><p>Usuario actual: <strong><?=h($user['username']??$user['email'])?></strong><br>Correo actual: <strong><?=h($user['email'])?></strong>. Para cambiar el usuario, el correo o la contraseña debes verificar la contraseña actual.</p><?php if($error):?><p class="error" role="alert"><?=h($error)?></p><?php endif;?><div class="tips"><strong>Mejoras V78</strong><ul><li>Ahora puedes iniciar sesión con <b>usuario</b> o con <b>correo</b>.</li><li>Al cambiar tus credenciales se cierran todas las sesiones anteriores.</li><li>La recuperación remota usa un enlace temporal de un solo uso válido por 20 minutos.</li><li>Si olvidaste también el usuario o no funciona el correo, puedes recuperar desde la terminal del servidor tras verificar identidad.</li></ul></div>
<form method="post" autocomplete="on"><input type="hidden" name="csrf" value="<?=h(csrfToken())?>"><div class="grid"><label>Nuevo usuario de acceso<input type="text" name="new_username" value="<?=h($user['username']??'')?>" required maxlength="60" pattern="[a-z0-9._-]{4,60}" autocomplete="username"></label><label>Nuevo correo de acceso<input type="email" name="new_email" value="<?=h($user['email'])?>" required maxlength="190" autocomplete="email"></label></div><label>Contraseña actual (obligatoria)<input type="password" name="current_password" required autocomplete="current-password"></label><div class="grid"><label>Nueva contraseña (dejar en blanco para conservar)<input type="password" name="new_password" minlength="12" maxlength="1024" autocomplete="new-password"></label><label>Confirmar nueva contraseña<input type="password" name="confirm_password" minlength="12" maxlength="1024" autocomplete="new-password"></label></div><p>Al guardar se cerrará tu sesión y las sesiones administrativas anteriores quedarán invalidadas. Entra de nuevo con el usuario, correo o contraseña actualizados.</p><button type="submit">Actualizar credenciales</button></form><p><a class="back" href="./editor.php">Volver al administrador</a></p></main></body></html>
