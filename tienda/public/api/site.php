<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
require dirname(__DIR__,2).'/app/calc_rules_validation.php';
require dirname(__DIR__,2).'/app/readiness.php';
require dirname(__DIR__,2).'/app/editor_permissions.php';
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
if($_SERVER['REQUEST_METHOD']==='GET'){
 try{
  $editor=(string)($_GET['scope']??'')==='editor';
  if($editor){sessionStart();if(!currentUser())jsonResponse(['error'=>'Acceso reservado al administrador'],401);}
  $res=db()->query('SELECT revision,config_json,updated_at FROM site_content WHERE id=1 LIMIT 1')->fetch();
  $config=$res?jsonDecode($res['config_json']):null;
  // El catálogo demo solo puede abrirse como vista previa del editor.
  // Por defecto no se sirve al público aunque alguien lo guarde en MariaDB.
  if($config!==null&&!$editor){
   if(($config['demoMode']??false)===true && getenv('MP_ALLOW_DEMO_PUBLIC')!=='1')$config=null;
   else $config=publicCmsConfig($config);
  }
  jsonResponse(['config'=>$config,'revision'=>$res?(int)$res['revision']:0,'updatedAt'=>$res['updated_at']??null]);
 }catch(Throwable $e){error_log('CMS GET: '.get_class($e));jsonResponse(['error'=>'El contenido publicado no está disponible.'],503);}
}
if($_SERVER['REQUEST_METHOD']!=='POST')jsonResponse(['error'=>'Método no admitido'],405);
try{
 sessionStart();if(!currentUser())jsonResponse(['error'=>'Debes iniciar sesión en /admin/'],401);
 $csrf=(string)($_SERVER['HTTP_X_CSRF_TOKEN']??'');
 if($csrf===''||!hash_equals(csrfToken(),$csrf))jsonResponse(['error'=>'Token CSRF inválido'],403);
 if((int)($_SERVER['CONTENT_LENGTH']??0)>5000000)jsonResponse(['error'=>'Archivo mayor de 5 MB'],413);
 $raw=file_get_contents('php://input',false,null,0,5000001);
 $input=json_decode($raw,true,512,JSON_THROW_ON_ERROR);
 if(!is_array($input)||!isset($input['config'])||!is_array($input['config']))jsonResponse(['error'=>'Formato de contenido inválido'],400);
 $config=$input['config'];if(!in_array((int)($config['schema']??0),[41,43],true))jsonResponse(['error'=>'Se admite el esquema CMS V41/V43'],400);$config['schema']=43;
 if(isset($config['technicalProfiles'])){
  if(!is_array($config['technicalProfiles'])||count($config['technicalProfiles'])>20)jsonResponse(['error'=>'Demasiados perfiles técnicos'],400);
  $seen=[];
  foreach($config['technicalProfiles'] as $profile){
    if(!is_array($profile))jsonResponse(['error'=>'Ficha técnica inválida'],400);
    $id=(string)($profile['id']??'');$name=trim((string)($profile['name']??''));
    if(!preg_match('/^[a-zA-Z0-9_-]{2,35}$/',$id)||isset($seen[$id])||in_array($id,['__proto__','prototype','constructor'],true)||$name==='')jsonResponse(['error'=>'Identificador de ficha inválido o repetido'],400);
    $seen[$id]=true;$total=$profile['totalWidth']??null;$useful=$profile['usefulWidth']??null;
    if(!is_numeric($total)||!is_numeric($useful)||(float)$total<=0.05||(float)$total>5||(float)$useful<=0.05||(float)$useful>(float)$total)jsonResponse(['error'=>'Anchos técnicos no válidos: '.$id],400);
    $lap=$profile['lap']??'';$hasLap=$lap!==''&&$lap!==null;
    if($hasLap&&(!is_numeric($lap)||(float)$lap<0||(float)$lap>2))jsonResponse(['error'=>'Traslape inválido: '.$id],400);
    $lengths=array_values(array_filter(array_map('trim',preg_split('/[;,\r\n]+/',(string)($profile['lengths']??'')))));
    if(count($lengths)>30)jsonResponse(['error'=>'Exceso de largos: '.$id],400);
    foreach($lengths as $length){if(!preg_match('/^\d+(?:\.\d+)?$/',$length)||(float)$length<.1||(float)$length>30||($hasLap&&(float)$length<=(float)$lap))jsonResponse(['error'=>'Largos no válidos: '.$id],400);}
    if(($profile['verified']??false)===true&&(!$hasLap||!count($lengths)||strlen(trim((string)($profile['source']??'')))<15))jsonResponse(['error'=>'Falta confirmar ficha técnica: '.$id],400);
  }
 }
 $maxItems=['products'=>500,'categories'=>80,'sections'=>30,'benefits'=>30];foreach($maxItems as $key=>$limit){if(isset($config[$key])&&(!is_array($config[$key])||count($config[$key])>$limit))jsonResponse(['error'=>'Datos fuera de límites: '.$key],400);}
 validateCmsCatalog($config);
 mpiValidateCalcRules($config['calcRules']??[]);
 // V93.6: la muestra permanece editable, pero no puede transformarse por error en contenido público.
 assertCommercialPublishing($config);
 if(isset($config['calculatorText'])){
  if(!is_array($config['calculatorText'])||count($config['calculatorText'])>20)throw new InvalidArgumentException('Textos de calculadoras inválidos');
  foreach($config['calculatorText'] as $key=>$value)if(!is_string($value)||strlen($value)>850)throw new InvalidArgumentException('Texto de calculadora demasiado largo');
 }
 $totalStrings=0;
 $scan=function($value)use(&$scan,&$totalStrings):void{
  if(is_array($value)){foreach($value as $v)$scan($v);return;}
  if(is_string($value)){
   $totalStrings+=strlen($value);
   if(strlen($value)>500000)throw new InvalidArgumentException('Texto o imagen excesivamente grande');
   if(str_contains($value,'<')||str_contains($value,'>'))throw new InvalidArgumentException('El contenido no admite código HTML');
  }elseif(!is_scalar($value)&&$value!==null)throw new InvalidArgumentException('Campo inválido');
 };
 $scan($config);
 if($totalStrings>4600000)jsonResponse(['error'=>'Contenido excesivo: usa imágenes en archivos externos'],400);
 $expected=(int)($input['expectedRevision']??-1);
 $pdo=db();$pdo->beginTransaction();
 try{
  $select=$pdo->query('SELECT revision,config_json FROM site_content WHERE id=1 FOR UPDATE')->fetch();
  $current=$select?(int)$select['revision']:0;
  // Seguridad de permisos aplicada en la API, no solo ocultando campos de la interfaz.
  if((currentUser()['role']??'')!=='admin'){
   $before=$select?jsonDecode($select['config_json']):[];
   $denial=mpiEditorProtectedChange($before,$config);
   if($denial!==null){$pdo->rollBack();jsonResponse(['error'=>$denial],403);}
  }
  if($expected!==$current){$pdo->rollBack();jsonResponse(['error'=>'Los datos han cambiado en otro navegador; recarga el administrador antes de publicar.','revision'=>$current],409);}
  $newRevision=$current+1;$config['revision']=$newRevision;$config['savedAt']=gmdate('c');
  $json=json_encode($config,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  $user=(int)currentUser()['id'];
  $stmt=$pdo->prepare('INSERT INTO site_content_history(revision,config_json,user_id) VALUES(?,?,?)');$stmt->execute([$newRevision,$json,$user]);
  if($select){$stmt=$pdo->prepare('UPDATE site_content SET revision=?, config_json=?, updated_by=? WHERE id=1');$stmt->execute([$newRevision,$json,$user]);}
  else{$stmt=$pdo->prepare('INSERT INTO site_content(id,revision,config_json,updated_by) VALUES(1,?,?,?)');$stmt->execute([$newRevision,$json,$user]);}
  $pdo->commit();jsonResponse(['ok'=>true,'revision'=>$newRevision]);
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
}catch(JsonException|InvalidArgumentException $e){jsonResponse(['error'=>$e->getMessage()],400);}catch(Throwable $e){error_log('CMS POST: '.$e->getMessage());jsonResponse(['error'=>'No fue posible guardar. Verifica conexión y permisos.'],500);}
