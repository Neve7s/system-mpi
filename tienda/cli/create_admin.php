<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli') {http_response_code(403);exit('Solo CLI');}
require dirname(__DIR__).'/app/bootstrap.php';
$email=strtolower(trim((string)($argv[1]??'')));
$username=normalizeAdminUsername((string)($argv[2]??''));
if(!filter_var($email,FILTER_VALIDATE_EMAIL)){fwrite(STDERR,"Uso: php cli/create_admin.php correo@empresa.com [usuario]\n");exit(1);} 
if($username===''){$username='admin'.date('His');}
if(!preg_match('/^[a-z0-9._-]{4,60}$/',$username)){fwrite(STDERR,"Usuario inválido. Usa 4 a 60 caracteres: letras minúsculas, números, punto, guion o guion bajo.\n");exit(1);} 
fwrite(STDOUT,"Contraseña del primer administrador (mínimo 12 caracteres): ");
$password=rtrim((string)fgets(STDIN),"\r\n");
if(strlen($password)<12){fwrite(STDERR,"Contraseña demasiado corta.\n");exit(1);} 
$hash=password_hash($password,PASSWORD_DEFAULT);
$db=db();$statement=$db->prepare("INSERT INTO admin_users(email,username,password_hash,role,active) VALUES(?,?,?,'admin',1)");
try{$statement->execute([$email,$username,$hash]);fwrite(STDOUT,"Administrador creado con usuario: $username. Borra el historial del terminal si muestra secretos.\n");}
catch(PDOException $e){fwrite(STDERR,"No se pudo crear el usuario (¿ya existe el correo o el usuario?).\n");exit(1);} 
