<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__).'/app/bootstrap.php';
require dirname(__DIR__).'/app/readiness.php';
require dirname(__DIR__).'/app/calc_rules_validation.php';
$issues=[];
$passed=[];
$testFile=null;
foreach(array_slice($argv,1) as $arg){if(str_starts_with($arg,'--fixture='))$testFile=substr($arg,10);}
if($testFile!==null){
 $raw=@file_get_contents($testFile);$config=is_string($raw)?json_decode($raw,true):null;
 if(!is_array($config)){fwrite(STDERR,"No se pudo leer la muestra JSON.
");exit(2);}
 echo "MODO SIMULADO: muestra JSON. NO certifica una instalación real.
";
}else{
 if(!in_array('mysql',PDO::getAvailableDrivers(),true))$issues[]='Falta PDO MySQL en este PHP';
 if(!$issues){try{
  $pdo=db();
  $names=$pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME IN ('admin_users','site_content','site_content_history','admin_login_limits')")->fetchAll(PDO::FETCH_COLUMN);
  foreach(['admin_users','site_content','site_content_history','admin_login_limits'] as $name)if(!in_array($name,$names,true))$issues[]='No existe tabla '.$name;
  if(!in_array('admin_users',$names,true)){}else{if((int)$pdo->query('SELECT COUNT(*) FROM admin_users WHERE active=1')->fetchColumn()<1)$issues[]='No hay un administrador activo';}
  if(in_array('site_content',$names,true)){$row=$pdo->query('SELECT config_json FROM site_content WHERE id=1')->fetchColumn();if(!$row)$issues[]='Aún no hay contenido publicado';else $config=jsonDecode($row);}
 }catch(Throwable $e){$issues[]='Falla conexión a MariaDB: '.get_class($e);}}
 $url=rtrim((string)getenv('MP_PUBLIC_BASE_URL'),'/');
 if(!str_starts_with($url,'https://')||!filter_var($url,FILTER_VALIDATE_URL))$issues[]='Falta URL HTTPS pública válida';
 if(!filter_var((string)getenv('MP_RESET_FROM'),FILTER_VALIDATE_EMAIL))$issues[]='Falta remitente real de recuperación de cuenta';
 if(!isset($config))$config=[];
}
if(count($config)){
 try{validateCmsCatalog($config);$passed[]='Categorías y productos coherentes';}catch(Throwable $e){$issues[]='Catálogo inválido: '.$e->getMessage();}
 try{mpiValidateCalcRules($config['calcRules']??[]);$passed[]='Reglas de cálculo V93 válidas y con ejemplos comprobados';}catch(Throwable $e){$issues[]='Fórmulas inválidas: '.$e->getMessage();}
 $issues=array_merge($issues,commercialIssues($config));
}else{$issues[]='Sin contenido comercial para evaluar';}
foreach($passed as $ok)echo 'OK · '.$ok."
";
foreach(array_unique($issues) as $problem)echo 'PENDIENTE · '.$problem."
";
if(!$issues){echo "Sin alertas automáticas; aún requiere pruebas de navegador, TLS externo, backups restaurados y revisión humana.
";exit(0);}
echo "ESTADO: NO PUBLICAR. Resuelve los pendientes y vuelve a ejecutar esta verificación.
";exit(1);
