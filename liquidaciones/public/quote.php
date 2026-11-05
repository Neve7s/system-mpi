<?php
declare(strict_types=1);
require dirname(__DIR__).'/private/bootstrap.php';mpi_require_transport();mpi_headers();mpi_session();
if(mpi_sso_enabled()){http_response_code(404);exit('Servicio no disponible en el sistema privado');} 
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');
function respond(array $data,int $code=200):never{http_response_code($code);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE);exit;}
if($_SERVER['REQUEST_METHOD']!=='POST'){header('Allow: POST');respond(['error'=>'Método no permitido'],405);}
mpi_check_csrf();
if((int)($_SERVER['CONTENT_LENGTH']??0)>32768)respond(['error'=>'Solicitud demasiado grande'],413);
$raw=file_get_contents('php://input',false,null,0,32769);if(strlen($raw)>32768)respond(['error'=>'Solicitud demasiado grande'],413);
$data=json_decode($raw,true);if(!is_array($data))respond(['error'=>'Solicitud inválida'],422);
if(!empty($data['website']))respond(['error'=>'Solicitud no válida'],422);
foreach(['customer_name','customer_phone','customer_email','customer_note'] as $field){if(isset($data[$field])&&!is_string($data[$field]))respond(['error'=>'Datos de contacto inválidos'],422);}
$name=trim((string)($data['customer_name']??''));$phone=trim((string)($data['customer_phone']??''));$email=trim((string)($data['customer_email']??''));$note=trim((string)($data['customer_note']??''));
if(strlen($name)<3||strlen($name)>120||!preg_match('/^[0-9+()\-\s]{7,30}$/',$phone)||strlen($email)>180||($email!==''&&!filter_var($email,FILTER_VALIDATE_EMAIL))||strlen($note)>1500||empty($data['consent']))respond(['error'=>'Revisa los datos de contacto y autoriza la respuesta a tu solicitud'],422);
$items=$data['items']??[];if(!is_array($items)||count($items)<1||count($items)>30)respond(['error'=>'Selecciona entre 1 y 30 productos'],422);
$ip=mpi_client_hash();$pdo=mpi_db();$lockHeld=false;$lockName='';
try{
 $pdo->beginTransaction();
 $lock=$pdo->prepare('SELECT GET_LOCK(?,5)');$lockName='mpi_quote_'.substr($ip,0,35);$lock->execute([$lockName]);if((int)$lock->fetchColumn()!==1)throw new RuntimeException('Solicitud ocupada');$lockHeld=true;
 $s=$pdo->prepare('SELECT COUNT(*) FROM quotes WHERE ip_hash=? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)');$s->execute([$ip]);if((int)$s->fetchColumn()>=5){$pdo->rollBack();$pdo->prepare('SELECT RELEASE_LOCK(?)')->execute([$lockName]);$lockHeld=false;respond(['error'=>'Demasiadas solicitudes desde esta conexión. Intenta más tarde.'],429);}
 $get=$pdo->prepare('SELECT p.id,p.name,p.sku,p.base_price,p.variants_json FROM products p JOIN categories c ON c.id=p.category_id WHERE p.id=? AND p.visible=1 AND c.visible=1');
 $lines=[];$total=0;$hasUnpriced=false;foreach($items as $line){
  if(!is_array($line))throw new InvalidArgumentException('Producto inválido');
  $id=filter_var($line['id']??null,FILTER_VALIDATE_INT);$qty=filter_var($line['qty']??null,FILTER_VALIDATE_INT);$vi=filter_var($line['variant']??null,FILTER_VALIDATE_INT);
  if(!$id||!$qty||$qty<1||$qty>10000||$vi===false||$vi===null||$vi<0)throw new InvalidArgumentException('Cantidad o presentación inválida');
  $get->execute([$id]);$p=$get->fetch();if(!$p)throw new InvalidArgumentException('Un producto ya no está disponible');
  $variants=json_decode($p['variants_json'],true);if(!is_array($variants))$variants=[];
  if($variants){if(!isset($variants[$vi]))throw new InvalidArgumentException('Presentación no disponible');$label=(string)$variants[$vi]['label'];$price=(float)$variants[$vi]['price'];}
  else{if($vi!==0)throw new InvalidArgumentException('Presentación inválida');$label='Estándar';$price=(float)$p['base_price'];}
  if($price<0||$price>100000000)throw new InvalidArgumentException('Precio inválido');
  if($price===0.0)$hasUnpriced=true;
  $subtotal=round($price*$qty,4);$total=round($total+$subtotal,4);if($total>9999999999)throw new InvalidArgumentException('Total excede el máximo');
  $lines[]=[$p['id'],$p['name'],$p['sku'],$label,$qty,$price,$subtotal];
 }
 $code='MPI'.strtoupper(bin2hex(random_bytes(8)));
 $q=$pdo->prepare('INSERT INTO quotes(public_code,customer_name,customer_phone,customer_email,customer_note,total,ip_hash) VALUES (?,?,?,?,?,?,?)');
 $q->execute([$code,$name,$phone,$email,$note,number_format($total,4,'.',''),$ip]);$qid=$pdo->lastInsertId();
 $qline=$pdo->prepare('INSERT INTO quote_items(quote_id,product_id,product_name,product_sku,variant,quantity,unit_price,subtotal) VALUES (?,?,?,?,?,?,?,?)');foreach($lines as $ln)$qline->execute(array_merge([$qid],$ln));
 $pdo->commit();$pdo->prepare('SELECT RELEASE_LOCK(?)')->execute([$lockName]);$lockHeld=false;
 $wa=mpi_setting('whatsapp');$url='';if(preg_match('/^[0-9]{8,15}$/',$wa)){$text='Hola, solicité la proforma '.$code.' en Mega Prime Import. Me llamo '.$name."\nProductos:".implode('',array_map(static fn($line)=>"\n- ".$line[1].' ('.$line[3].') × '.$line[4],$lines));$url='https://wa.me/'.$wa.'?text='.rawurlencode($text);}
 respond(['ok'=>true,'code'=>$code,'total'=>number_format($total,2,'.',''),'has_unpriced'=>$hasUnpriced,'whatsapp_url'=>$url],201);
}catch(InvalidArgumentException $e){if($pdo->inTransaction())$pdo->rollBack();if($lockHeld){try{$pdo->prepare('SELECT RELEASE_LOCK(?)')->execute([$lockName]);}catch(Throwable $ignored){}}respond(['error'=>$e->getMessage()],422);}
catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();if($lockHeld){try{$pdo->prepare('SELECT RELEASE_LOCK(?)')->execute([$lockName]);}catch(Throwable $ignored){}}error_log('MPI quote error: '.$e->getMessage());respond(['error'=>'No se pudo registrar la solicitud.'],503);}
