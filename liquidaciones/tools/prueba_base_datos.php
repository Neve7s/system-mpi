<?php
/** Diagnóstico de la DB real; no cambia ni elimina datos. */
declare(strict_types=1);
if(PHP_SAPI!=='cli'){http_response_code(403);exit;}
require dirname(__DIR__).'/private/bootstrap.php';
if(!is_file(MPI_ROOT.'/private/config.php')){fwrite(STDERR,"Falta private/config.php: ejecute el instalador primero.\n");exit(1);}
if(!extension_loaded('pdo_mysql')){fwrite(STDERR,"Falta pdo_mysql en PHP CLI.\n");exit(1);}
try{
 $db=mpi_db();
 $tables=['users','login_attempts','site_settings','categories','products','quotes','quote_items','calculator_backups','audit_events'];
 foreach($tables as $table){$s=$db->query("SHOW TABLES LIKE ".$db->quote($table));if(!$s->fetchColumn())throw new RuntimeException('Tabla ausente: '.$table);echo "OK · $table\n";}
 $u=$db->query("SELECT COUNT(*) FROM users WHERE role='admin' AND active=1")->fetchColumn();if((int)$u<1)throw new RuntimeException('No existe un administrador activo.');echo "OK · administrador activo\n";
 $db->beginTransaction();
 $tmp='V85 TEST '.bin2hex(random_bytes(4));
 $insert=$db->prepare('INSERT INTO categories(name,description,sort_order,visible) VALUES (?,?,?,?)');$insert->execute([$tmp,'Prueba de escritura revertida',99999,0]);$id=$db->lastInsertId();
 $get=$db->prepare('SELECT name FROM categories WHERE id=?');$get->execute([$id]);if($get->fetchColumn()!==$tmp)throw new RuntimeException('INSERT/SELECT incorrecto');
 $db->rollBack();$get->execute([$id]);if($get->fetchColumn()!==false)throw new RuntimeException('Rollback no funcionó');echo "OK · transacción, lectura y rollback\n";
 echo "ÉXITO: DB accesible y transaccional, sin cambios persistentes.\n";
}catch(Throwable $e){if(isset($db)&&$db instanceof PDO&&$db->inTransaction())$db->rollBack();fwrite(STDERR,'ERROR: '.$e->getMessage()."\n");exit(1);}
