<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='POST')jsonResponse(['error'=>'Método no permitido'],405);
sessionStart();if(!currentUser())jsonResponse(['error'=>'Debes iniciar sesión'],401);
$token=(string)($_SERVER['HTTP_X_CSRF_TOKEN']??'');if($token===''||!hash_equals(csrfToken(),$token))jsonResponse(['error'=>'Sesión inválida'],403);
$file=$_FILES['image']??null;if(!$file||($file['error']??UPLOAD_ERR_NO_FILE)!==UPLOAD_ERR_OK)jsonResponse(['error'=>'No fue posible subir la imagen'],400);
if((int)$file['size']>3000000||$file['size']<10)jsonResponse(['error'=>'Imagen demasiado grande o vacía (máximo 3 MB)'],413);
$mime=(new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);$ext=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'][$mime]??null;
if($ext===null)jsonResponse(['error'=>'Solo JPEG, PNG y WEBP'],415);
$dimension=@getimagesize($file['tmp_name']);if(!$dimension||$dimension[0]<1||$dimension[1]<1||$dimension[0]>8000||$dimension[1]>8000||$dimension[0]*$dimension[1]>20000000)jsonResponse(['error'=>'Dimensiones de imagen no válidas'],400);
$dir=dirname(__DIR__).'/uploads/media';if(!is_dir($dir)&&!mkdir($dir,0755,true))jsonResponse(['error'=>'No se pudo preparar el directorio'],500);
$fileName=bin2hex(random_bytes(16)).'.'.$ext;$path=$dir.'/'.$fileName;
if(!move_uploaded_file($file['tmp_name'],$path))jsonResponse(['error'=>'No fue posible guardar el archivo'],500);
chmod($path,0644);
jsonResponse(['url'=>'/uploads/media/'.$fileName]);
