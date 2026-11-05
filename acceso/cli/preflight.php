<?php
declare(strict_types=1);
require dirname(__DIR__).'/private/bootstrap.php';
$errors=[];$warnings=[];$ok=[];
function check(bool $cond,string $label):void {global $errors,$ok;if($cond)$ok[]=$label;else $errors[]=$label;}
check(PHP_VERSION_ID>=80200,'PHP >=8.2');
check(extension_loaded('pdo_mysql'),'Extensión PDO MySQL');
check(extension_loaded('curl'),'Extensión cURL');
check(extension_loaded('openssl'),'Extensión OpenSSL');
$file=dirname(__DIR__).'/private/config.php';check(is_file($file),'Archivo acceso/private/config.php configurado');
if(is_file($file)){
 $c=require $file;
 check(is_array($c),'Configuración legible');
 if(is_array($c)){
  check(preg_match('~^https://~',(string)($c['base_url']??''))===1,'URL HTTPS del centro de acceso');
  foreach(['tienda','liquidaciones'] as $client){
   $s=$c['clients'][$client]??[];check(!empty($s['secret'])&&strlen((string)$s['secret'])>=32&&!str_contains((string)$s['secret'],'REEMPLAZAR'),'Secreto configurado para '.$client);
   check(preg_match('~^https://~',(string)($s['redirect_uri']??''))===1,'Callback HTTPS de '.$client);
  }
  if(extension_loaded('pdo_mysql')){
    try{$p=id_store();$n=(int)$p->query('SELECT COUNT(*) FROM admin_users')->fetchColumn();$ok[]='Usuarios de tienda accesibles (' . $n . ')';}
    catch(Throwable $e){$errors[]='No se puede consultar tienda: '.get_class($e);}
    try{$p=id_db_identity();foreach(['identity_sessions','identity_codes','identity_grants'] as $table){$p->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();$ok[]='Tabla identidad: '.$table;}}
    catch(Throwable $e){$errors[]='No se puede consultar identidad: '.get_class($e);}
  }
 }
}
echo "MEGA PRIME IMPORT — DIAGNÓSTICO DE INTEGRACIÓN\n";
foreach($ok as $item)echo "[OK] $item\n";
foreach($warnings as $item)echo "[AVISO] $item\n";
foreach($errors as $item)echo "[FALTA] $item\n";
echo 'Resultado: '.(count($errors)?'NO APTO para activar SSO':'APTO para siguiente prueba web')."\n";
exit(count($errors)?2:0);
