<?php
declare(strict_types=1);
/** Comprobaciones comerciales de consistencia, NO sustituyen aprobación humana. */
function commercialIssues(array $c): array {
 $issues=[];
 if(($c['demoMode']??false)===true)$issues[]='MODO DEMO ACTIVO: sustituir y verificar todos los datos ficticios antes de publicar';
 $fields=[(string)($c['header']['topRight']??''),(string)($c['hero']['note']??''),(string)($c['footer']['tag']??''),(string)($c['footer']['location']??''),(string)($c['footer']['address']??'')];
 foreach($fields as $value){if(preg_match('/prototipo|demostrativ|fictici|texto de ejemplo|lorem ipsum/i',$value)){$issues[]='Contiene rótulos de demostración';break;}}
 // Conservar los archivos de demostración no equivale a publicar fotografías reales.
 $fixture=dirname(__DIR__).'/fixtures/demo_catalog.json';
 if(is_file($fixture)){
   $demo=json_decode((string)file_get_contents($fixture),true);
   if(is_array($demo)){
     $demoPaths=[];
     $collect=function($value)use(&$collect,&$demoPaths):void{
       if(is_array($value)){foreach($value as $v)$collect($v);}
       elseif(is_string($value)&&preg_match('~^/uploads/products/[a-f0-9]{32}\.(?:jpg|png|webp)$~',$value))$demoPaths[$value]=true;
     };
     $collect($demo);
     $retained=[];$scan=function($value)use(&$scan,&$retained,$demoPaths):void{
       if(is_array($value)){foreach($value as $v)$scan($v);}
       elseif(is_string($value)&&isset($demoPaths[$value]))$retained[$value]=true;
     };
     $scan($c);
     if(count($retained))$issues[]='Todavía se utilizan '.count($retained).' imágenes ficticias de demostración: sustituir por fotografías verificadas';
   }
 }
 foreach([(string)($c['footer']['email']??''),(string)($c['footer']['phone']??''),(string)($c['footer']['hours']??'')] as $v){
   if(preg_match('/\.example\b|\bejem(?:plo)?\b|\bfictici|\b000[- ]?000/i',$v)){
     $issues[]='Contacto o horario comercial contiene datos ficticios';break;
   }
 }
 if(($c['seo']['indexable']??false)!==true)$issues[]='Indexación deshabilitada: confirmar decisión comercial antes de lanzar';
 $number=preg_replace('/[^0-9]/','',(string)($c['whatsapp']['number']??''));
 if(!preg_match('/^[1-9][0-9]{7,14}$/',$number))$issues[]='Falta configurar un número comercial WhatsApp válido (prefijo internacional)';
 $categories=[];
 foreach(($c['categories']??[]) as $cat){if(is_array($cat)&&($cat['visible']??true)!==false)$categories[(string)($cat['name']??'')]=true;}
 if(!count($categories))$issues[]='No hay categorías visibles';
 $products=[];
 foreach(($c['products']??[]) as $p){if(is_array($p)&&($p['published']??true)!==false&&isset($categories[(string)($p['category']??'')]))$products[]=$p;}
 if(!count($products))$issues[]='No hay productos publicados en categorías visibles';
 foreach($products as $p){if(str_starts_with((string)($p['image']??''),'photo-')){$issues[]='Hay fotografías externas ilustrativas: comprobar licencias y reemplazar si es necesario';break;}}
 foreach(($c['social']['items']??[]) as $item){if(is_array($item)&&($item['visible']??true)!==false&&empty($item['url'])){$issues[]='Hay redes sociales visibles sin URL';break;}}
 if(!trim((string)($c['footer']['address']??'')) || preg_match('/^(ecuador|por confirmar|direcci[oó]n por confirmar)$/i',trim((string)($c['footer']['address']??''))))$issues[]='Falta domicilio o referencia comercial verificada (solo país no es suficiente)';
 if(trim((string)($c['footer']['legalNote']??''))==='')$issues[]='Falta revisar aviso legal y tratamiento de datos personales';
 return array_values(array_unique($issues));
}

/** V93.6: no convertir una muestra en catálogo comercial omitiendo la etiqueta DEMO.
 * Una configuración de ensayo puede guardarse y editarse; la salida de modo DEMO
 * exige resolver todos los avisos de publicación antes de publicarse. */
function assertCommercialPublishing(array $config):void {
    if (!array_key_exists('demoMode', $config) || !is_bool($config['demoMode'])) {
        throw new InvalidArgumentException('Define explícitamente el modo DEMO antes de guardar.');
    }
    if ($config['demoMode'] === true) return;
    $issues = commercialIssues($config);
    if ($issues) {
        throw new InvalidArgumentException('No se puede desactivar DEMO todavía. Corrige: '.implode(' | ',array_slice($issues,0,5)));
    }
}
