<?php
/** V76: comando seguro para verificar MariaDB. Ejemplos:
 * php cli/verify_persistence.php
 * php cli/verify_persistence.php --rollback-write-test   (SOLO base terminada en _test)
 */
declare(strict_types=1);
require dirname(__DIR__).'/app/bootstrap.php';
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
try{
 $pdo=db();$schema=(string)$pdo->query('SELECT DATABASE()')->fetchColumn();
 echo "Base actual: ".$schema."\n";
 foreach(['admin_users','site_content','site_content_history'] as $table){
  $n=(int)$pdo->query("SELECT COUNT(*) FROM `".$table."`")->fetchColumn();
  echo $table.': '.$n." fila(s)\n";
 }
 $current=$pdo->query('SELECT revision,config_json FROM site_content WHERE id=1')->fetch();
 if(!$current){echo "No hay una publicación inicial. Entra al panel y guarda una configuración.\n";exit(0);}
 $config=json_decode($current['config_json'],true,512,JSON_THROW_ON_ERROR);
 echo 'Revisión publicada: '.(int)$current['revision'].' · Productos: '.count($config['products']??[])."\n";
 $last=$pdo->query('SELECT revision FROM site_content_history ORDER BY revision DESC LIMIT 1')->fetchColumn();
 if((int)$last!==(int)$current['revision'])throw new RuntimeException('La última revisión del historial no coincide con el contenido');
 echo "Historial: coincide con la revisión publicada.\n";
 if(!in_array('--rollback-write-test',$argv,true)){echo "Prueba de lectura correcta. No se modificaron los datos.\n";exit(0);}
 if(!preg_match('/_test$/i',$schema))throw new RuntimeException('Por seguridad, la prueba de escritura solo se permite en una base cuyo nombre termine en _test');
 $adminId=$pdo->query('SELECT id FROM admin_users WHERE active=1 ORDER BY id LIMIT 1')->fetchColumn();
 if(!$adminId)throw new RuntimeException('Primero crea un administrador de pruebas');
 $pdo->beginTransaction();
 $q=$pdo->prepare('INSERT INTO site_content_history(revision,config_json,user_id) VALUES(?,?,?)');
 $q->execute([(int)$current['revision'],$current['config_json'],(int)$adminId]);
 $newId=(int)$pdo->lastInsertId();
 $test=$pdo->prepare('SELECT COUNT(*) FROM site_content_history WHERE id=?');$test->execute([$newId]);
 if((int)$test->fetchColumn()!==1)throw new RuntimeException('La escritura de prueba no pudo leerse');
 $pdo->rollBack();$test->execute([$newId]);
 if((int)$test->fetchColumn()!==0)throw new RuntimeException('La prueba no pudo revertirse');
 echo "Escritura/lectura/rollback verificados. No quedaron registros de prueba.\n";
}catch(Throwable $e){if(isset($pdo)&&$pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,'ERROR: '.$e->getMessage()."\n");exit(1);}
