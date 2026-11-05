<?php
declare(strict_types=1);
// Verificación no destructiva. No revela secretos ni escribe en la base de datos.
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/calc_rules_validation.php';
$failed=false;
function reportCheck(string $label,bool $pass,string $hint=''):void{
 global $failed;
 echo ($pass?'OK':'PENDIENTE')." - $label".($hint!==''?' · '.$hint:'')."\n";
 if(!$pass)$failed=true;
}
reportCheck('PHP 8.1 o superior',PHP_VERSION_ID>=80100,PHP_VERSION);
reportCheck('Extensión PDO MySQL',in_array('mysql',PDO::getAvailableDrivers(),true));
reportCheck('Extensión fileinfo',extension_loaded('fileinfo'));
// V93.7: la lectura de planos usa IA local, OCR y PDF en el navegador.
echo 'INFO - Lectura de planos: navegador del visitante (sin API externa de pago)'.PHP_EOL;
echo 'INFO - OCR/PDF: bibliotecas libres descargadas por el navegador si están disponibles'.PHP_EOL;
reportCheck('Aleatoriedad segura',function_exists('random_bytes'));
reportCheck('Directorio public',is_dir(dirname(__DIR__).'/public'));
reportCheck('Directorio privado app fuera de public',realpath(dirname(__DIR__).'/app')!==false&&strpos((string)realpath(dirname(__DIR__).'/app'),(string)realpath(dirname(__DIR__).'/public').DIRECTORY_SEPARATOR)!==0);
reportCheck('Permiso para guardar imágenes',is_writable(dirname(__DIR__).'/public/uploads/media'));
$url=rtrim((string)getenv('MP_PUBLIC_BASE_URL'),'/');
reportCheck('URL de recuperación HTTPS',str_starts_with($url,'https://')&&filter_var($url,FILTER_VALIDATE_URL)!==false,'configura MP_PUBLIC_BASE_URL al activar correos');
$from=(string)getenv('MP_RESET_FROM');
reportCheck('Remitente de recuperación',filter_var($from,FILTER_VALIDATE_EMAIL)!==false,'configura MP_RESET_FROM al activar correos');
if(in_array('mysql',PDO::getAvailableDrivers(),true)){
 try{
  $db=db();$schema=(string)$db->query('SELECT DATABASE()')->fetchColumn();
  echo 'Base de datos configurada: '.$schema."\n";
  $tables=$db->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('site_content','site_content_history','admin_users','admin_password_resets','admin_recovery_limits','admin_login_limits')")->fetchAll(PDO::FETCH_COLUMN);
  foreach(['admin_users','site_content','site_content_history','admin_password_resets','admin_recovery_limits','admin_login_limits'] as $table){reportCheck('Tabla '.$table,in_array($table,$tables,true));}
  $columns=$db->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='admin_users'")->fetchAll(PDO::FETCH_COLUMN);
  foreach(['username','auth_version','password_hash'] as $col)reportCheck('Campo admin_users.'.$col,in_array($col,$columns,true));
  if(in_array('site_content',$tables,true)){
   $record=$db->query('SELECT revision,config_json FROM site_content WHERE id=1')->fetch();
   reportCheck('Contenido publicado',is_array($record),'publica la primera revisión desde el administrador');
   if($record){$cfg=jsonDecode($record['config_json']);try{validateCmsCatalog($cfg);reportCheck('Categorías y productos consistentes',true);}catch(Throwable $e){reportCheck('Categorías y productos consistentes',false,$e->getMessage());}try{mpiValidateCalcRules($cfg['calcRules']??[]);reportCheck('Fórmulas V93 aprobadas y probadas',true);}catch(Throwable $e){reportCheck('Fórmulas V93 aprobadas y probadas',false,$e->getMessage());}}
  }
 }catch(Throwable $e){reportCheck('Conexión MariaDB',false,get_class($e));}
}
echo "\nComprobaciones no incluidas: HTTPS en el proxy, entrega de correos, navegador real, recuperación de backups y configuración de servidor.\n";
exit($failed?1:0);
