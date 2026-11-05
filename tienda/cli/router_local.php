<?php
// SOLO para `php -S localhost:18088 -t public cli/router_local.php`.
// Simula respuesta 404 para rutas inexistentes; no usar como router de producción.
declare(strict_types=1);
$public=realpath(dirname(__DIR__).'/public');
$uri=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH)?:'/';
$path=realpath($public.'/'.ltrim(rawurldecode($uri),'/'));
// Prueba local: bloquear archivos ocultos e intentos de servir scripts subidos.
$pieces=explode('/',trim(rawurldecode($uri),'/'));
if(array_filter($pieces,static fn($v)=>$v!==''&&$v[0]==='.')){http_response_code(404);echo '404';exit;}
if(preg_match('~^/uploads/.*\.(?:php\d*|phtml|phar|cgi|pl|sh|svg|html?)$~i',$uri)){http_response_code(404);echo '404';exit;}
if($uri==='/'||$uri==='/index.html')return false;
if(!$path||(!str_starts_with($path,$public.'/')&&$path!==$public)){
 http_response_code(404);header('Content-Type: text/plain; charset=utf-8');echo '404 No encontrado';exit;
}
if(is_dir($path)){
 if(is_file($path.'/index.php')){require $path.'/index.php';exit;}
 if(is_file($path.'/index.html')){header('Content-Type: text/html; charset=utf-8');readfile($path.'/index.html');exit;}
 http_response_code(403);exit;
}
return false;
