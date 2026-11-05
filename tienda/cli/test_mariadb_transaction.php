<?php
/**
 * V90: comprobación real de lecturas/escrituras MariaDB con ROLLBACK final.
 * Exclusivamente en base nueva y privada: *staging*, *test*, *demo* o *prueba*.
 * No modifica ni elimina información de una tienda publicada.
 */
declare(strict_types=1);
if (PHP_SAPI!=='cli') {http_response_code(403);exit;}
require dirname(__DIR__).'/app/bootstrap.php';
function testAssert(bool $yes,string $message): void {
 if(!$yes)throw new RuntimeException('FALLO: '.$message);
 echo 'OK · '.$message."\n";
}
$confirm='';foreach($argv as $a)if(str_starts_with($a,'--confirm-db='))$confirm=substr($a,13);
if($confirm==='' || !preg_match('/(?:staging|prueba|test|demo)/i',$confirm)){
 fwrite(STDERR,"BLOQUEADO: solo base aislada de PRUEBAS, confirmar nombre mediante --confirm-db=...\n"); exit(2);
}
if(!in_array('mysql',PDO::getAvailableDrivers(),true)){
 fwrite(STDERR,"BLOQUEADO: PDO MySQL no instalado; no se realizaron escrituras.\n");exit(2);
}
try{$pdo=db();$actual=(string)$pdo->query('SELECT DATABASE()')->fetchColumn();}
catch(Throwable $e){fwrite(STDERR,"Error de conexión a MariaDB. No se realizaron escrituras.\n");exit(2);}
if($actual!==$confirm){fwrite(STDERR,"BLOQUEADO: el nombre confirmado no coincide con la base realmente conectada.\n");exit(2);}
$name=$actual;
testAssert($actual===$name,'Conectado a la base de pruebas indicada');
$admin=$pdo->query('SELECT id FROM admin_users WHERE active=1 ORDER BY id LIMIT 1')->fetchColumn();
testAssert((bool)$admin,'Existe al menos un administrador de pruebas');
$before=$pdo->query('SELECT revision,config_json FROM site_content WHERE id=1')->fetch();
$previous=$before?(int)$before['revision']:null;
$config=$before?jsonDecode($before['config_json']):jsonDecode((string)file_get_contents(dirname(__DIR__).'/fixtures/demo_catalog.json'));
$existing=[];foreach(($config['products']??[]) as $product)$existing[]=(int)($product['id']??0);
$id=max($existing?:[0])+1234;
$cat='PRUEBA V90 CATEGORÍA';$empty='PRUEBA V90 VACÍA';
foreach(($config['categories']??[]) as $category)if(in_array((string)($category['name']??''),[$cat,$empty],true))throw new RuntimeException('Los nombres de prueba ya existen; no se modificó contenido');
$config['categories'][]=['name'=>$cat,'description'=>'Categoría temporal','image'=>'','visible'=>true];
$config['categories'][]=['name'=>$empty,'description'=>'Sin productos','image'=>'','visible'=>true];
$config['products'][]=['id'=>$id,'name'=>'PRODUCTO V90 SIMULADO','category'=>$cat,'subcategory'=>'otros','description'=>'Prueba de transacción','price'=>13.25,'published'=>true,'image'=>'','presets'=>[],'gallery'=>[]];
$config['products'][]=['id'=>$id+1,'name'=>'PRODUCTO V90 OCULTO','category'=>$cat,'subcategory'=>'otros','description'=>'Prueba de visibilidad','price'=>3.25,'published'=>false,'image'=>'','presets'=>[],'gallery'=>[]];
validateCmsCatalog($config);
$public=publicCmsConfig($config);
testAssert(count(array_filter($public['categories'],fn($c)=>($c['name']??'')===$empty))===1,'Categoría vacía incluida en vista pública');
testAssert(count(array_filter($public['products'],fn($p)=>($p['name']??'')==='PRODUCTO V90 OCULTO'))===0,'Producto oculto excluido de vista pública');
$pdo->beginTransaction();
try {
 $locked=$pdo->query('SELECT revision FROM site_content WHERE id=1 FOR UPDATE')->fetch();
 $rev=$locked?(int)$locked['revision']:0;
 testAssert($rev===($previous??0),'Bloqueo de revisión de contenido');
 $next=$rev+1;$config['revision']=$next;$config['savedAt']=gmdate('c');
 $data=json_encode($config,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
 if($locked){$q=$pdo->prepare('UPDATE site_content SET revision=?,config_json=?,updated_by=? WHERE id=1');$q->execute([$next,$data,$admin]);}
 else {$q=$pdo->prepare('INSERT INTO site_content(id,revision,config_json,updated_by) VALUES(1,?,?,?)');$q->execute([$next,$data,$admin]);}
 $pdo->prepare('INSERT INTO site_content_history(revision,config_json,user_id) VALUES(?,?,?)')->execute([$next,$data,$admin]);
 $found=$pdo->query('SELECT revision,config_json FROM site_content WHERE id=1')->fetch();
 testAssert($found&&(int)$found['revision']===$next,'Escritura y relectura del contenido en MariaDB');
 $read=jsonDecode((string)$found['config_json']);
 testAssert(count($read['categories']??[])===count($config['categories']),'Persistencia de categorías');
 testAssert(count($read['products']??[])===count($config['products']),'Persistencia de productos');
 $count=$pdo->prepare('SELECT COUNT(*) FROM site_content_history WHERE revision=?');$count->execute([$next]);
 testAssert((int)$count->fetchColumn()>=1,'Historial de revisiones en MariaDB');
 $pdo->rollBack();
 $after=$pdo->query('SELECT revision FROM site_content WHERE id=1')->fetch();
 testAssert(($after?(int)$after['revision']:null)===$previous,'ROLLBACK: ningún cambio permanente en contenido');
 echo "MARIADB V90: PRUEBA COMPLETADA. No se modificaron registros publicados.\n";
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,'Error de integración: '.$e->getMessage()."\n");exit(1);}
