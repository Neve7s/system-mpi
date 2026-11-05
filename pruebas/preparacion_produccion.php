<?php
declare(strict_types=1);
/** Diagnóstico NO destructivo: ejecutar en servidor desde terminal como: php pruebas/preparacion_produccion.php */
if(PHP_SAPI!=='cli'){http_response_code(403);exit('Solo terminal.');}
$root=dirname(__DIR__);$ok=[];$fail=[];
function probe(bool $condition,string $label):void {global $ok,$fail;if($condition){$ok[]=$label;}else{$fail[]=$label;}echo ($condition?'[OK] ':'[PENDIENTE] ').$label."\n";}
function readPrivate(string $path):?array {if(!is_file($path))return null;$v=require $path;return is_array($v)?$v:null;}
function privateDb(array $config):PDO {
 return new PDO('mysql:host='.$config['host'].';port='.(int)($config['port']??3306).';dbname='.$config['name'].';charset=utf8mb4',$config['user'],$config['password'],[
  PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES=>false,PDO::ATTR_TIMEOUT=>4]);
}
function queryCheck(?array $config,string $label,array $tables):void {
 if(!$config||!extension_loaded('pdo_mysql')){probe(false,'Conexión MariaDB: '.$label);return;}
 try{
  $pdo=privateDb($config);$dbName=(string)$pdo->query('SELECT DATABASE()')->fetchColumn();
  probe($dbName===(string)($config['name']??''),'Conexión MariaDB y base correcta: '.$label);
  foreach($tables as $table){if(!preg_match('/^[a-z_]+$/D',$table))continue;
   $stmt=$pdo->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=?');$stmt->execute([$table]);
   probe((int)$stmt->fetchColumn()===1,'Tabla '.$label.'.'.$table);
  }
 }catch(Throwable $e){probe(false,'Conexión MariaDB o tablas: '.$label.' (revisar credenciales y permisos)');}
}
echo "MEGA PRIME IMPORT — LISTA AUTOMÁTICA DE PRODUCCIÓN V1.1 (NO MODIFICA DATOS)\n";
probe(PHP_VERSION_ID>=80200,'PHP 8.2+');
foreach(['pdo_mysql','curl','openssl','fileinfo','mbstring','json'] as $ext)probe(extension_loaded($ext),'Módulo PHP '.$ext);
$shop=readPrivate($root.'/tienda/app/config.local.php');
$finance=readPrivate($root.'/liquidaciones/private/config.php');
$identity=readPrivate($root.'/acceso/private/config.php');
$storeSSO=readPrivate($root.'/tienda/app/sso.config.php');
$financeSSO=readPrivate($root.'/liquidaciones/private/sso.config.php');
foreach(['Tienda'=>$shop,'Liquidaciones'=>$finance,'Identidad'=>$identity,'SSO tienda'=>$storeSSO,'SSO liquidaciones'=>$financeSSO] as $label=>$cfg)probe(is_array($cfg),'Configuración privada '.$label);
$storeDb=$shop;
$finDb=$finance['db']??null;
$idDb=$identity['identity_db']??null;
$idStoreDb=$identity['store_db']??null;
$names=array_filter([(string)($storeDb['name']??''),(string)($finDb['name']??''),(string)($idDb['name']??'')]);
probe(count($names)===3&&count(array_unique($names))===3,'Tres bases con nombres independientes');
probe($storeDb&&$idStoreDb&&$storeDb['name']===$idStoreDb['name'],'Centro consulta exactamente la base de usuarios de tienda');
probe(($identity['require_https']??null)===true,'Centro exige HTTPS');
probe(($storeSSO['enabled']??null)===true&&($financeSSO['enabled']??null)===true,'SSO activado en ambas aplicaciones');
foreach(['tienda'=>$storeSSO,'liquidaciones'=>$financeSSO] as $key=>$client){
 $upstream=$identity['clients'][$key]??[];
 probe(is_array($client)&&is_array($upstream)&&($client['redirect_uri']??null)===($upstream['redirect_uri']??null),'URL callback exacta para '.$key);
 probe(is_array($client)&&is_array($upstream)&&strlen((string)($client['client_secret']??''))>=32&&hash_equals((string)($client['client_secret']??''),(string)($upstream['secret']??'')),'Secreto SSO coincidente y no vacío para '.$key);
}
queryCheck($storeDb,'tienda',['admin_users','site_content','site_content_history']);
queryCheck($finDb,'liquidaciones',['users','identity_user_links','calculator_backups','audit_events']);
queryCheck($idDb,'identidad',['identity_sessions','identity_codes','identity_grants','identity_login_limits']);
if($storeDb&&extension_loaded('pdo_mysql')){
 try{
  $pdo=privateDb($storeDb);$n=(int)$pdo->query("SELECT COUNT(*) FROM admin_users WHERE active=1 AND role='admin'")->fetchColumn();
  probe($n>=1,'Al menos un administrador de tienda activo');
  $q=$pdo->query('SELECT config_json FROM site_content WHERE id=1')->fetchColumn();
  $data=is_string($q)?json_decode($q,true):null;
  probe(is_array($data),'Contenido CMS de tienda guardado en MariaDB');
  if(is_array($data)){
   probe(!empty($data['demoMode']),'Datos DEMO identificados: solo pruebas, no ventas reales');
   echo '[DATO] Categorías en MariaDB: '.count($data['categories']??[]).' · Productos: '.count($data['products']??[])."\n";
  }
 }catch(Throwable $e){probe(false,'Validación de cuentas y catálogo desde MariaDB');}
}
echo "\nRESULTADO: ".count($ok)." correctos, ".count($fail)." pendientes.\n";
echo "NOTA: estas comprobaciones no prueban DNS, TLS público, correo, navegadores físicos ni transacciones funcionales; completar la lista de aceptación.\n";
exit($fail?2:0);
