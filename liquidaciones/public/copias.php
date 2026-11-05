<?php
declare(strict_types=1);
require dirname(__DIR__).'/private/bootstrap.php';
mpi_require_transport();mpi_headers(true);mpi_session();
header('Content-Type: application/json; charset=utf-8');
function mpi_json($value,int $code=200):never{http_response_code($code);echo json_encode($value,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
$u=mpi_user();if(!$u)mpi_json(['error'=>'Sesión requerida'],401);
if(!in_array($u['role'],['admin','analyst'],true))mpi_json(['error'=>'Permiso insuficiente'],403);
$pdo=mpi_db();
if($_SERVER['REQUEST_METHOD']==='GET'){
 if(isset($_GET['id'])){
  $id=filter_var($_GET['id'],FILTER_VALIDATE_INT);if(!$id)mpi_json(['error'=>'ID inválido'],400);
  $s=$pdo->prepare('SELECT contents FROM calculator_backups WHERE id=? AND (user_id=? OR ?=1)');$s->execute([$id,$u['id'],$u['role']==='admin'?1:0]);$r=$s->fetch();
  if(!$r)mpi_json(['error'=>'Copia no encontrada'],404);
  header('Content-Type: application/json; charset=utf-8');echo $r['contents'];exit;
 }
 $s=$pdo->prepare('SELECT b.id,b.label,b.byte_size,b.created_at,u.display_name AS author FROM calculator_backups b JOIN users u ON u.id=b.user_id WHERE b.user_id=? OR ?=1 ORDER BY b.id DESC LIMIT 50');
 $s->execute([$u['id'],$u['role']==='admin'?1:0]);mpi_json(['items'=>$s->fetchAll()]);
}
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Allow: GET, POST');mpi_json(['error'=>'Método no permitido'],405);}
mpi_check_csrf();
if((int)($_SERVER['CONTENT_LENGTH']??0)>8388608)mpi_json(['error'=>'Copia excede 8 MB'],413);
$raw=file_get_contents('php://input',false,null,0,8388609);if(strlen($raw)>8388608)mpi_json(['error'=>'Copia excede 8 MB'],413);
$obj=json_decode($raw,true,128);if(!is_array($obj)||($obj['formato']??'')!=='costos-importacion-ecuador-pro'||!in_array((int)($obj['version']??0),[2,3],true)||!isset($obj['itemsImportacion'])||!is_array($obj['itemsImportacion'])||!array_is_list($obj['itemsImportacion'])||count($obj['itemsImportacion'])>5000)mpi_json(['error'=>'Formato de copia inválido'],422);
if(!isset($obj['nombreCopia'])||!is_string($obj['nombreCopia']))mpi_json(['error'=>'Indique un nombre de copia válido'],422);
$label=mb_safe(trim($obj['nombreCopia']),120);if($label==='')mpi_json(['error'=>'Indique nombre de copia'],422);
foreach($obj['itemsImportacion'] as $item){
 if(!is_array($item)||!is_string($item['codigo']??null)||!is_string($item['producto']??null))mpi_json(['error'=>'Partida inválida'],422);
 foreach(['cantidad','fobUnitario'] as $key){
  $value=$item[$key]??null;
  if(!is_scalar($value)||is_bool($value)||!is_numeric($value)||!is_finite((float)$value)||(float)$value<=0)mpi_json(['error'=>'Cantidad o FOB inválido'],422);
 }
}
try{
 $pdo->beginTransaction();
 $s=$pdo->prepare('INSERT INTO calculator_backups(user_id,label,contents,byte_size) VALUES (?,?,?,?)');$s->execute([$u['id'],$label,$raw,strlen($raw)]);
 $backupId=(int)$pdo->lastInsertId();
 mpi_audit((int)$u['id'],'backup_create',(string)$backupId);
 $pdo->commit();
 mpi_json(['ok'=>true,'id'=>$backupId,'label'=>$label],201);
}catch(Throwable $e){
 if($pdo->inTransaction())$pdo->rollBack();
 error_log('MPI backup: '.$e->getMessage());
 mpi_json(['error'=>'No se pudo guardar la copia. Conserva un respaldo JSON e inténtalo nuevamente.'],503);
}
