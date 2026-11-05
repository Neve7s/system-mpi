<?php
// Laboratorio local: php -S 127.0.0.1:8080 -t public tools/router-desarrollo.php
// ¡El servidor integrado no debe exponerse a Internet!
$path=parse_url($_SERVER['REQUEST_URI']??'/',PHP_URL_PATH);
if(!is_string($path)){http_response_code(404);exit;}
if($path==='/')return false;
if(preg_match('~(^|/)\.|\.php$|\.(?:html|json|zip|sql|bak|env|ini|log|conf|sqlite|db|pem|key)$~i',$path)){
 if(!in_array($path,['/index.php','/admin.php','/panel.php','/quote.php','/privacidad.php','/copias.php','/sesion.php'],true)){http_response_code(404);exit('No encontrado');}
}
if(str_starts_with($path,'/uploads/')&&!preg_match('~^/uploads/[a-f0-9]{32}\.(jpg|png|webp)$~D',$path)){http_response_code(404);exit('No encontrado');}
return false;
