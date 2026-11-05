<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
header('Content-Type: text/html; charset=utf-8');header('Cache-Control: no-store, private');header('Referrer-Policy: no-referrer');header('X-Frame-Options: DENY');header('X-Content-Type-Options: nosniff');
sessionStart();
function recoveryEnabled():bool {
 $url=rtrim((string)getenv('MP_PUBLIC_BASE_URL'),'/');$from=(string)getenv('MP_RESET_FROM');
 return str_starts_with($url,'https://')&&filter_var($url,FILTER_VALIDATE_URL)!==false&&filter_var($from,FILTER_VALIDATE_EMAIL)!==false&&function_exists('mail');
}
function throttleRecovery(string $key):bool {
 $db=db();$hash=hash('sha256','mpi-recovery-v78:'.$key);$q=$db->prepare('INSERT INTO admin_recovery_limits (key_hash,attempts,window_started) VALUES (?,1,NOW()) ON DUPLICATE KEY UPDATE attempts=IF(window_started < NOW()-INTERVAL 1 HOUR,1,attempts+1),window_started=IF(window_started < NOW()-INTERVAL 1 HOUR,NOW(),window_started)');$q->execute([$hash]);
 $q=$db->prepare('SELECT attempts FROM admin_recovery_limits WHERE key_hash=?');$q->execute([$hash]);return (int)$q->fetchColumn()<=5;
}
$feedback='';$error='';$finished=false;
$token=trim((string)($_POST['token']??$_GET['token']??''));$validToken=(bool)preg_match('/^[a-f0-9]{64}$/D',$token);
if($_SERVER['REQUEST_METHOD']==='POST'){
 try{
  checkCsrf();$action=(string)($_POST['action']??'');
  if($action==='request'){
   if(!recoveryEnabled())throw new RuntimeException('La recuperación por correo todavía no está configurada en el servidor. Contacta al administrador del hosting.');
   $identifier=trim((string)($_POST['identifier']??''));
   $identifierLower=strtolower($identifier);
   if($identifierLower===''||strlen($identifierLower)>190)throw new RuntimeException('Introduce tu usuario o correo de acceso.');
   $feedback='Si la cuenta existe y el servidor tiene correo saliente habilitado, recibirás instrucciones para recuperar el acceso.';
   if(throttleRecovery('ip:'.($_SERVER['REMOTE_ADDR']??'unknown'))&&throttleRecovery('identifier:'.$identifierLower)){
    $q=db()->prepare('SELECT id,email FROM admin_users WHERE (email=? OR username=?) AND active=1 LIMIT 1');$q->execute([$identifierLower,$identifierLower]);$row=$q->fetch();
    $id=$row['id']??null;
    if($id){
     $raw=bin2hex(random_bytes(32));$hash=hash('sha256',$raw);
     $pdo=db();$pdo->beginTransaction();
     try{$q=$pdo->prepare('UPDATE admin_password_resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL');$q->execute([$id]);
      $q=$pdo->prepare('INSERT INTO admin_password_resets (user_id,token_hash,expires_at) VALUES (?,?,DATE_ADD(NOW(), INTERVAL 20 MINUTE))');$q->execute([$id,$hash]);$pdo->commit();
     }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
     $url=rtrim((string)getenv('MP_PUBLIC_BASE_URL'),'/').'/admin/recover.php?token='.$raw;
     $body="Solicitud de recuperación de Mega Prime Import.\n\nAbre este enlace para crear una contraseña nueva (20 minutos, solo un uso):\n".$url."\n\nSi no pediste este cambio, ignora el mensaje.\n";
     $from=(string)getenv('MP_RESET_FROM');
     $sent=@mail((string)$row['email'],'Recuperacion de acceso - Mega Prime Import',$body,'From: '.$from."\r\n".'Content-Type: text/plain; charset=UTF-8');
     if(!$sent){$q=$pdo->prepare('DELETE FROM admin_password_resets WHERE token_hash=?');$q->execute([$hash]);error_log('Reset email delivery failed');}
    }
   }
   $finished=true;
  }elseif($action==='reset'){
   if(!$validToken)throw new RuntimeException('El enlace de recuperación no es válido.');
   $password=(string)($_POST['password']??'');$confirm=(string)($_POST['confirm_password']??'');
   if(strlen($password)<12||strlen($password)>1024||$password!==$confirm)throw new RuntimeException('Usa una contraseña de al menos 12 caracteres y confírmala correctamente.');
   $pdo=db();$pdo->beginTransaction();
   try{
    $q=$pdo->prepare('SELECT r.id,r.user_id FROM admin_password_resets r INNER JOIN admin_users u ON u.id=r.user_id WHERE r.token_hash=? AND r.used_at IS NULL AND r.expires_at>NOW() AND u.active=1 FOR UPDATE');$q->execute([hash('sha256',$token)]);$record=$q->fetch();
    if(!$record)throw new RuntimeException('El enlace ha caducado o ya se utilizó. Solicita uno nuevo.');
    $q=$pdo->prepare('UPDATE admin_users SET password_hash=?,auth_version=auth_version+1 WHERE id=?');$q->execute([password_hash($password,PASSWORD_DEFAULT),$record['user_id']]);
    $q=$pdo->prepare('UPDATE admin_password_resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL');$q->execute([$record['user_id']]);$pdo->commit();
   }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
   unset($_SESSION['auth']);session_regenerate_id(true);$feedback='Contraseña actualizada. Ya puedes entrar al administrador.';$finished=true;$token='';
  }else throw new RuntimeException('Solicitud inválida.');
 }catch(Throwable $e){error_log('Admin recovery: '.get_class($e));$error=$e instanceof RuntimeException?$e->getMessage():'No se pudo completar la recuperación. Comprueba la configuración del servidor.';}
}
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Recuperar acceso · Mega Prime Import</title><style>:root{color-scheme:dark}body{background:#091219;color:#f7f5ec;font:15px system-ui,Arial;display:grid;place-items:center;min-height:100vh;padding:20px;box-sizing:border-box;margin:0}main{width:100%;max-width:560px;background:#162731;padding:25px;border:1px solid #bd9950;border-radius:15px;box-sizing:border-box}h1{font-size:23px;color:#e7c46d}p{line-height:1.55;color:#c7d4dc}label{display:block;margin:12px 0}input{box-sizing:border-box;width:100%;background:#07151b;border:1px solid #7b8c90;color:white;border-radius:7px;padding:12px;margin-top:6px;font:inherit}button{background:#edc96b;color:#121b20;padding:12px;border:0;border-radius:7px;font-weight:800;cursor:pointer}a{color:#f0ce7c}.error{color:#ffb6b6}.good{color:#bff2d3}.info{margin:16px 0;padding:14px;border-radius:12px;background:#0d181e;border:1px solid #374750}.info ul{margin:8px 0 0 18px;padding:0}.info li{margin:6px 0}</style></head><body><main><h1>Recuperar acceso</h1><div class="info"><strong style="color:#f0ce7c">Opciones disponibles</strong><ul><li>Recuperación por enlace de un solo uso enviado al correo registrado.</li><li>Validez del enlace: 20 minutos.</li><li>Si olvidaste tu usuario y también tu correo, usa la recuperación verificada desde la terminal del servidor.</li><li>No se envían contraseñas por WhatsApp ni por SMS.</li></ul></div><?php if($error):?><p role="alert" class="error"><?=h($error)?></p><?php endif;?><?php if($feedback):?><p role="status" class="good"><?=h($feedback)?></p><?php endif;?>
<?php if(!$finished):?>
<?php if($token!==''):?>
<?php if($validToken):?><p>Escribe una nueva contraseña para tu cuenta. El enlace solo puede utilizarse una vez y tiene una validez de 20 minutos.</p><form method="post"><input type="hidden" name="csrf" value="<?=h(csrfToken())?>"><input type="hidden" name="action" value="reset"><input type="hidden" name="token" value="<?=h($token)?>"><label>Nueva contraseña<input type="password" name="password" autocomplete="new-password" minlength="12" maxlength="1024" required></label><label>Confirmar contraseña<input type="password" name="confirm_password" autocomplete="new-password" minlength="12" maxlength="1024" required></label><button>Establecer contraseña</button></form><?php else:?><p class="error">El enlace no es válido. Solicita uno nuevo.</p><?php endif;?>
<?php else:?>
<p>Introduce el usuario o el correo de acceso registrado. Si existe y el servidor tiene habilitado el correo saliente, recibirás un enlace temporal en el correo asociado.</p><form method="post"><input type="hidden" name="csrf" value="<?=h(csrfToken())?>"><input type="hidden" name="action" value="request"><label>Usuario o correo de la cuenta<input type="text" name="identifier" autocomplete="username" maxlength="190" required></label><button>Enviar enlace de recuperación</button></form><p>Si olvidaste también el correo de acceso, solicita al responsable del servidor una recuperación verificada por consola. No se envían contraseñas por WhatsApp.</p>
<?php endif;?><?php endif;?>
<p><a href="./index.php">Volver al inicio de sesión</a></p></main></body></html>
