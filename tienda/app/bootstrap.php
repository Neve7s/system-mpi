<?php
declare(strict_types=1);
require_once __DIR__.'/login_guard.php';
require_once __DIR__.'/sso_client.php';
// Cabeceras seguras para respuestas PHP; no impone SSL durante pruebas HTTP locales.
if (!headers_sent()) {
 header('X-Content-Type-Options: nosniff');
 header('Referrer-Policy: strict-origin-when-cross-origin');
 header('X-Frame-Options: SAMEORIGIN');
 header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
 header("Content-Security-Policy: base-uri 'self'; object-src 'none'; frame-ancestors 'self'; form-action 'self'");
 if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') {
   header('Strict-Transport-Security: max-age=31536000');
 }
}

// Configuración exclusiva del servidor. La carpeta app NO se publica.
function db(): PDO {
    static $pdo=null;
    if ($pdo instanceof PDO) return $pdo;
    // Credenciales fuera de public/, mediante variables de entorno o app/config.local.php.
    $localFile=__DIR__.'/config.local.php';
    $local=is_file($localFile)?require $localFile:[];
    if(!is_array($local))throw new RuntimeException('Configuración local inválida');
    $host=getenv('MP_DB_HOST') ?: ($local['host']??'127.0.0.1');
    $port=getenv('MP_DB_PORT') ?: ($local['port']??'3306');
    $name=getenv('MP_DB_NAME') ?: ($local['name']??'');
    $user=getenv('MP_DB_USER') ?: ($local['user']??'');
    $pass=getenv('MP_DB_PASSWORD') ?: ($local['password']??'');
    if ($name==='' || $user==='') throw new RuntimeException('Falta configurar MP_DB_NAME/MP_DB_USER');
    $pdo=new PDO('mysql:host='.$host.';port='.$port.';dbname='.$name.';charset=utf8mb4',$user,$pass,[
      PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
      PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
      PDO::ATTR_EMULATE_PREPARES=>false,
      PDO::ATTR_TIMEOUT=>5,
    ]);
    return $pdo;
}
function jsonResponse(array $data,int $status=200):never {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store, private');
    header('X-Content-Type-Options: nosniff');
    echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES|JSON_THROW_ON_ERROR);
    exit;
}
function sessionStart():void {
    if (session_status()===PHP_SESSION_ACTIVE) return;
    ini_set('session.use_strict_mode','1');
    ini_set('session.use_only_cookies','1');
    session_name('mpi_cms_session');
    session_set_cookie_params([
      'lifetime'=>0,'path'=>'/','secure'=>!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off',
      'httponly'=>true,'samesite'=>'Lax',
    ]);
    session_start();
}
function csrfToken():string {sessionStart();return $_SESSION['csrf']??=bin2hex(random_bytes(32));}
function checkCsrf():void {sessionStart();if(!hash_equals(csrfToken(),(string)($_POST['csrf']??'')))throw new RuntimeException('Token de sesión inválido');}

function normalizeAdminUsername(string $raw):string {
    $value=strtolower(trim($raw));
    $value=preg_replace('/[^a-z0-9._-]+/','',$value) ?? '';
    return substr($value,0,60);
}

function currentUser():?array {
 sessionStart();$session=$_SESSION['auth']??null;
 if(!is_array($session)||!isset($session['id']))return null;
 // V90: fin de sesión tras 30 min sin actividad o 12 h desde el acceso.
 // El cambio fuerza un nuevo login para sesiones anteriores sin marcas temporales.
 $now=time();$started=(int)($session['signed_in_at']??0);$seen=(int)($session['last_seen_at']??0);
 if(!$started||!$seen||$now-$started>43200||$now-$seen>1800||$started>$now+60||$seen>$now+60){
    unset($_SESSION['auth']);return null;
 }
 $_SESSION['auth']['last_seen_at']=$now;
 if(mpi_sso_enabled()){
    $identity=$_SESSION['sso_identity']??null;
    $proof=is_array($identity)?mpi_sso_status($identity):null;
    if(!$proof||(int)($proof['user_id']??0)!==(int)$session['id']||(int)($proof['auth_version']??0)!==(int)($session['auth_version']??0)||($proof['role']??'')!=='store'){
       unset($_SESSION['auth'],$_SESSION['sso_identity']);return null;
    }
 }

 try{
  $stmt=db()->prepare('SELECT email,username,role,active,auth_version FROM admin_users WHERE id=? LIMIT 1');
  $stmt->execute([(int)$session['id']]);$u=$stmt->fetch();
  if(!$u||(int)$u['active']!==1||(int)$u['auth_version']!==(int)($session['auth_version']??0)){
   unset($_SESSION['auth']);return null;
  }
  $_SESSION['auth']['email']=$u['email'];$_SESSION['auth']['username']=$u['username']??$u['email'];$_SESSION['auth']['role']=$u['role'];return $_SESSION['auth'];
 }catch(Throwable $e){error_log('Admin session verification failed: '.get_class($e));return null;}
}
function needAdmin():array { $user=currentUser();if(!$user){header('Location: /admin/');exit;}return $user;}
function h(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function jsonDecode(?string $value):array {if(!$value)return [];try{$v=json_decode($value,true,512,JSON_THROW_ON_ERROR);return is_array($v)?$v:[];}catch(Throwable){return [];}}
function colorList(string $raw):array {return array_slice(array_values(array_unique(array_filter(array_map('trim',explode(',',$raw))))),0,30);}
function amount(mixed $raw):?float {if($raw===null||$raw==='')return null;if(!is_numeric($raw)||!is_finite((float)$raw)||floatval($raw)<0||floatval($raw)>100000000)return null;return round((float)$raw,4);}
function imageUrl(?string $value):?string {
 $value=trim((string)$value);if($value==='')return null;
 if(preg_match('~^/uploads/(?:products|media)/[a-f0-9]{32}\.(jpg|png|webp)$~',$value))return $value;
 if(filter_var($value,FILTER_VALIDATE_URL)&&str_starts_with($value,'https://')&&strlen($value)<=512)return $value;
 throw new InvalidArgumentException('Imagen: usa HTTPS o una fotografía cargada en el panel');
}

/** Vista pública del CMS. Nunca expone categorías o productos ocultos en la API. */
function publicCmsConfig(array $config):array {
    $visible=[];$names=[];
    foreach(($config['categories']??[]) as $cat){
        if(!is_array($cat)||($cat['visible']??true)===false)continue;
        $name=trim((string)($cat['name']??''));
        if($name==='')continue;
        $visible[]=$cat;$names[$name]=true;
    }
    $config['categories']=$visible;
    $products=[];$ids=[];
    foreach(($config['products']??[]) as $product){
        if(!is_array($product)||($product['published']??true)===false)continue;
        if(!isset($names[(string)($product['category']??'')]))continue;
        $products[]=$product;$ids[(int)($product['id']??0)]=true;
    }
    $config['products']=$products;
    if(isset($config['hero']['featuredIds'])&&is_array($config['hero']['featuredIds'])){
        $config['hero']['featuredIds']=array_values(array_filter($config['hero']['featuredIds'],static fn($id)=>isset($ids[(int)$id])));
    }
    if(isset($config['header']['nav'])&&is_array($config['header']['nav'])){
        $config['header']['nav']=array_values(array_filter($config['header']['nav'],static fn($link)=>is_array($link)&&(($link['action']??'')!=='category'||isset($names[(string)($link['target']??'')]))));
    }
    if(isset($config['footer']['imports'])&&is_array($config['footer']['imports'])){
        $config['footer']['imports']=array_values(array_filter($config['footer']['imports'],static fn($line)=>is_array($line)&&isset($names[(string)($line['category']??'')])));
    }
    if(isset($config['reviews']['items'])&&is_array($config['reviews']['items'])){
        $config['reviews']['items']=array_values(array_filter($config['reviews']['items'],static fn($review)=>is_array($review)&&($review['approved']??false)===true&&($review['permissionConfirmed']??false)===true));
    }
    if(isset($config['customBlocks'])&&is_array($config['customBlocks'])){
        $config['customBlocks']=array_values(array_filter($config['customBlocks'],static fn($block)=>is_array($block)&&($block['visible']??true)!==false));
    }
    return $config;
}
/** Validación previa al guardado: las nuevas categorías y productos deben ser coherentes. */
function validateCmsCatalog(array $config):void {
    $names=[];
    foreach(($config['categories']??[]) as $cat){
        if(!is_array($cat))throw new InvalidArgumentException('Categoría inválida');
        $name=trim((string)($cat['name']??''));
        if($name===''||strlen($name)>90)throw new InvalidArgumentException('El nombre de una categoría debe tener entre 1 y 90 caracteres');
        $key=strtolower($name);
        if(isset($names[$key]))throw new InvalidArgumentException('Hay categorías duplicadas: '.$name);
        $names[$key]=$name;
    }
    $ids=[];
    foreach(($config['products']??[]) as $product){
        if(!is_array($product))throw new InvalidArgumentException('Producto inválido');
        $id=$product['id']??null;
        if(!is_numeric($id)||(int)$id<1||(float)$id!==(float)(int)$id||isset($ids[(int)$id]))throw new InvalidArgumentException('ID de producto inválido o duplicado');
        $ids[(int)$id]=true;
        $name=trim((string)($product['name']??''));
        if($name===''||strlen($name)>180)throw new InvalidArgumentException('El nombre del producto debe tener entre 1 y 180 caracteres');
        $category=strtolower(trim((string)($product['category']??'')));
        if(!isset($names[$category]))throw new InvalidArgumentException('El producto "'.$name.'" pertenece a una categoría inexistente. Reasígnalo antes de guardar.');
        $price=$product['price']??0;
        if($price!==''&&(!is_numeric($price)||!is_finite((float)$price)||(float)$price<0||(float)$price>100000000))throw new InvalidArgumentException('Precio inválido en producto: '.$name);
        if(isset($product['gallery'])&&(!is_array($product['gallery'])||count($product['gallery'])>12))throw new InvalidArgumentException('La galería supera el máximo de 12 fotos: '.$name);
        if(isset($product['presets'])&&(!is_array($product['presets'])||count($product['presets'])>60))throw new InvalidArgumentException('Demasiadas presentaciones en producto: '.$name);
    }
}
