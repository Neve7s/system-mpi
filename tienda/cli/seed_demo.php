<?php
/**
 * Carga datos FICTICIOS en staging, únicamente si el CMS está vacío.
 * Requiere PDO MySQL, base exclusiva para pruebas y administrador existente.
 * NO se ejecuta automáticamente; jamás usar sobre tiendas existentes.
 */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__).'/app/bootstrap.php';
$apply=in_array('--apply',$argv,true);
$confirm='';foreach($argv as $argument){if(str_starts_with($argument,'--confirm-db='))$confirm=substr($argument,13);}
$fixture=dirname(__DIR__).'/fixtures/demo_catalog.json';
$config=json_decode((string)file_get_contents($fixture),true,512,JSON_THROW_ON_ERROR);
validateCmsCatalog($config);
if(($config['demoMode']??false)!==true)throw new RuntimeException('La muestra debe indicar demoMode=true');
if(!$apply){echo "SOLO VERIFICACION: muestra DEMO válida (".count($config['categories'])." categorías, ".count($config['products'])." productos).\n";
 echo "Para usar sobre una base NUEVA y PRIVADA: configurar MariaDB mediante MP_DB_* o app/config.local.php y ejecutar --apply --confirm-db=nombre_de_base_demo\n";exit(0);}
// Admite MP_DB_NAME O app/config.local.php. Solo escribe tras confirmar la base REAL conectada.
if($confirm===''||!preg_match('/(?:demo|staging|prueba|test)/i',$confirm)){
 fwrite(STDERR,"BLOQUEADO: confirma una base exclusiva de pruebas con --confirm-db=nombre_demo.\n");exit(2);
}
if(!in_array('mysql',PDO::getAvailableDrivers(),true)){fwrite(STDERR,"Falta PDO MySQL. No se modificó ninguna base.\n");exit(2);}
try{$pdo=db();$actual=(string)$pdo->query('SELECT DATABASE()')->fetchColumn();}
catch(Throwable $e){fwrite(STDERR,"Error conectando a la base de pruebas. No se modificó ninguna base.\n");exit(2);}
if($confirm!==$actual){fwrite(STDERR,"BLOQUEADO: --confirm-db no coincide con la base MariaDB conectada.\n");exit(2);}
$envName=$actual;
$pdo->beginTransaction();
try{
 $row=$pdo->query('SELECT COUNT(*) FROM site_content')->fetchColumn();
 if((int)$row!==0)throw new RuntimeException('La base ya tiene contenidos: no se sobrescribe ni se agrega nada');
 $admin=$pdo->query("SELECT id FROM admin_users WHERE active=1 AND role='admin' ORDER BY id ASC LIMIT 1")->fetchColumn();
 if(!$admin)throw new RuntimeException('Primero crea un administrador en esta base de pruebas');
 $config['revision']=1;$config['savedAt']=gmdate('c');
 $json=json_encode($config,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
 $pdo->prepare('INSERT INTO site_content (id, revision, config_json, updated_by) VALUES(1,1,?,?)')->execute([$json,$admin]);
 $pdo->prepare('INSERT INTO site_content_history (revision,config_json,user_id) VALUES(1,?,?)')->execute([$json,$admin]);
 $pdo->commit();echo "CONTENIDO DEMO INICIAL GUARDADO en ".$envName.". TODAVIA NO PUBLICAR.\n";
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,'No se modificó el contenido: '.$e->getMessage()."\n");exit(1);}
