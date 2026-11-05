<?php
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
if(PHP_SAPI!=='cli')exit(1);
$cmd=$argv[1]??'';$who=strtolower(trim((string)($argv[2]??'')));
if($cmd==='list'){
 foreach(db()->query('SELECT id,username,email,role,active FROM admin_users ORDER BY id') as $u)echo $u['id']." ".$u['username']." <".$u['email']."> (".$u['role'].", active=".$u['active'].")\n";
 exit;
}
if($cmd==='reset'&&$who!==''){
 fwrite(STDERR,"Usa esta herramienta solo tras verificar la titularidad. Nueva contraseña (12+ caracteres): ");
 $pw=trim((string)fgets(STDIN));if(strlen($pw)<12||strlen($pw)>1024){fwrite(STDERR,"Contraseña no válida\n");exit(2);} 
 $db=db();$q=$db->prepare('UPDATE admin_users SET password_hash=?,auth_version=auth_version+1 WHERE (email=? OR username=?) AND active=1');$q->execute([password_hash($pw,PASSWORD_DEFAULT),$who,$who]);
 if(!$q->rowCount()){fwrite(STDERR,"Cuenta no encontrada\n");exit(3);} 
 $q=$db->prepare('UPDATE admin_password_resets SET used_at=NOW() WHERE user_id=(SELECT id FROM admin_users WHERE email=? OR username=? LIMIT 1) AND used_at IS NULL');$q->execute([$who,$who]);echo "Contraseña cambiada. Todas las sesiones anteriores serán rechazadas.\n";exit;
}
fwrite(STDERR,"Uso: php cli/admin_recovery.php list | reset usuario_o_correo\n");exit(2);
