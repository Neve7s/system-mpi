<?php
declare(strict_types=1);
// SOLO terminal. Vincula una cuenta financiera existente a la identidad autorizada de la tienda.
if(PHP_SAPI!=='cli'){http_response_code(404);exit;}
require dirname(__DIR__).'/private/bootstrap.php';
$opts=getopt('',['tienda-id:','liquidacion-id:','confirmar:']);
$storeId=filter_var($opts['tienda-id']??null,FILTER_VALIDATE_INT);
$localId=filter_var($opts['liquidacion-id']??null,FILTER_VALIDATE_INT);
if(!$storeId||!$localId){echo "Uso: php tools/vincular_usuario_historico.php --tienda-id=2 --liquidacion-id=5 [--confirmar=VINCULAR]\n";exit(2);}
$pdo=mpi_db();$q=$pdo->prepare('SELECT id,username,role,active FROM users WHERE id=?');$q->execute([$localId]);$user=$q->fetch();
if(!$user||!(int)$user['active']){fwrite(STDERR,"Cuenta financiera inexistente o desactivada.\n");exit(3);}
$q=$pdo->prepare('SELECT local_user_id FROM identity_user_links WHERE store_user_id=? OR local_user_id=?');$q->execute([$storeId,$localId]);
if($q->fetch()){fwrite(STDERR,"Ya existe una vinculación para alguno de esos IDs.\n");exit(4);}
echo "Identidad de la tienda #$storeId → cuenta financiera #$localId (".$user['username'].", rol ".$user['role'].").\n";
echo "Confirma POR TU CUENTA que ambos IDs pertenecen a la misma persona. Nunca enlaces solo por coincidencia de nombre.\n";
if(($opts['confirmar']??'')!=='VINCULAR'){echo "Simulación: no se ha modificado ninguna base de datos.\n";exit(0);}
$pdo->beginTransaction();
try{
 $pdo->prepare('INSERT INTO identity_user_links(store_user_id,local_user_id) VALUES (?,?)')->execute([$storeId,$localId]);
 $pdo->prepare('UPDATE users SET credential_version=credential_version+1 WHERE id=?')->execute([$localId]);
 $pdo->commit();echo "Vinculación creada. Las sesiones previas fueron invalidadas.\n";
}catch(Throwable $e){$pdo->rollBack();fwrite(STDERR,"No se pudo vincular (".get_class($e).").\n");exit(5);}
