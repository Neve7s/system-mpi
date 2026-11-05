<?php
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
$root=dirname(__DIR__);
$checks=[];
function mpi_ini_bytes(string $value):int{ $value=trim($value);if($value==='-1')return PHP_INT_MAX;$unit=strtolower(substr($value,-1));$n=(float)$value;return (int)($n*match($unit){'g'=>1073741824,'m'=>1048576,'k'=>1024,default=>1});}

function verify(string $name,bool $ok,string $detail=''):void{global $checks;$checks[]=[$ok?'OK':'PENDIENTE',$name,$detail];}
verify('PHP 8.2+',PHP_VERSION_ID>=80200,PHP_VERSION);
foreach(['pdo_mysql','fileinfo','openssl','json','session'] as $ext)verify('Extensión '.$ext,extension_loaded($ext));
verify('Configuración privada',is_file($root.'/private/config.php'),'private/config.php');
verify('Subidas de imágenes',is_dir($root.'/public/uploads')&&is_writable($root.'/public/uploads'),'public/uploads (escritura PHP)');
verify('Directorio público',is_file($root.'/public/index.php')&&is_file($root.'/public/panel.php'),'Configure DocumentRoot apuntando a public/');
verify('PHP upload_max_filesize >= 6MB',mpi_ini_bytes((string)ini_get('upload_max_filesize'))>=6*1048576,(string)ini_get('upload_max_filesize'));
verify('PHP post_max_size >= 10MB',mpi_ini_bytes((string)ini_get('post_max_size'))>=10*1048576,(string)ini_get('post_max_size'));
if(is_file($root.'/private/config.php')){
 $cfg=require $root.'/private/config.php';
 verify('HTTP local de pruebas desactivado',empty($cfg['allow_local_http']));
 if(extension_loaded('pdo_mysql')&&is_array($cfg['db']??null)){
  try{$d=$cfg['db'];$pdo=new PDO('mysql:host='.$d['host'].';port='.(int)($d['port']??3306).';dbname='.$d['name'].';charset=utf8mb4',$d['user'],$d['password'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
  $tables=['users','login_attempts','site_settings','categories','products','quotes','quote_items','calculator_backups','audit_events'];
  $all=true;foreach($tables as $t){$s=$pdo->query("SHOW TABLES LIKE '".$t."'");if(!$s->fetchColumn())$all=false;}
  verify('MariaDB y tablas V85',$all,'Conexión SQL y 9 tablas');
  if($all){$v=$pdo->query("SELECT setting_value FROM site_settings WHERE setting_key='demo_mode'")->fetchColumn();verify('Aviso de demostración retirado para lanzamiento',$v!=='1','Retíralo desde Panel → Diseño y contenido solo al verificar información real.');}
  }catch(Throwable $e){verify('MariaDB y tablas V85',false,'No hay conexión o esquema instalado.');}
 }
}
foreach($checks as [$st,$k,$d])echo str_pad('['.$st.']',14).' '.$k.($d?' · '.$d:'')."\n";
echo "\nAdemás, compruebe manualmente certificado HTTPS, DNS, PHP-FPM, vhost, permisos web, copias y dispositivos físicos.\n";
exit(count(array_filter($checks,static fn($c)=>$c[0]==='PENDIENTE'))>0?1:0);
