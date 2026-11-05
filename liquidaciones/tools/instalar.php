<?php
declare(strict_types=1);
if (PHP_SAPI!=='cli'){http_response_code(403);exit;}
$root=dirname(__DIR__);$target=$root.'/private/config.php';
if(is_file($target)){fwrite(STDERR,"Hay una instalación existente; no se modifica.\n");exit(1);}
if(PHP_VERSION_ID<80200||!extension_loaded('pdo_mysql')||!extension_loaded('fileinfo')){fwrite(STDERR,"Se requiere PHP 8.2+ con extensiones pdo_mysql y fileinfo.\n");exit(1);}
function mb_strtolower_safe(string $s):string{return function_exists('mb_strtolower')?mb_strtolower($s,'UTF-8'):strtolower($s);}
function ask(string $name,bool $secret=false):string{
 fwrite(STDOUT,$name.': ');
 if($secret && DIRECTORY_SEPARATOR==='/' && function_exists('shell_exec')){@shell_exec('stty -echo 2>/dev/null');}
 $v=rtrim((string)fgets(STDIN),"\r\n");
 if($secret && DIRECTORY_SEPARATOR==='/' && function_exists('shell_exec')){@shell_exec('stty echo 2>/dev/null');fwrite(STDOUT,"\n");}
 return $v;
}
fwrite(STDOUT,"Mega Prime Import V85 · INSTALACIÓN EN UNA BASE DEDICADA Y VACÍA.\nNo borra ni modifica otras aplicaciones.\n");
$host=ask('Host MariaDB [127.0.0.1]')?:'127.0.0.1';$port=ask('Puerto [3306]')?:'3306';
$name=ask('Base de datos dedicada (debe existir)');$dbUser=ask('Usuario MariaDB');$dbPass=ask('Contraseña MariaDB',true);
$admin=ask('Usuario administrador inicial (3-100 letras/números/._@-)');$display=ask('Nombre visible administrador');$pass=ask('Contraseña administrador (mínimo 12 caracteres)',true);
$local=strtolower(ask('¿Permitir HTTP SOLO en localhost para laboratorio? [no]'))==='si';
$demoAnswer=mb_strtolower_safe(ask('¿Instalar productos de DEMOSTRACIÓN con precios por confirmar? [si]'));$demo=$demoAnswer===''||in_array($demoAnswer,['si','sí','s','yes','y'],true);
if(!preg_match('/^[a-zA-Z0-9_.@-]{3,100}$/',$admin)||strlen($pass)<12||strlen($pass)>72||strlen($name)<1||!preg_match('/^[a-zA-Z0-9_]+$/D',$name)||!ctype_digit($port)||strlen($display)<2||strlen($display)>120){fwrite(STDERR,"Valores iniciales inválidos.\n");exit(1);}
try{$pdo=new PDO("mysql:host=$host;port=$port;dbname=$name;charset=utf8mb4",$dbUser,$dbPass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_EMULATE_PREPARES=>false]);}
catch(Throwable $e){fwrite(STDERR,"No se pudo conectar a MariaDB. Compruebe host, permisos y DB existente.\n");exit(1);}
$existingTables=$pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_NUM);
if(count($existingTables)>0){fwrite(STDERR,"La base contiene tablas; por seguridad solo se instala en una base completamente VACÍA.\n");exit(1);}
$sql=file_get_contents($root.'/private/schema.sql');
// DDL de MySQL hace commits implícitos. La base debe estar VACÍA y respaldada.
try{
 foreach(preg_split('/;\s*(?:\n|$)/',$sql) as $statement){$statement=preg_replace('/^\s*--[^\n]*\n/m','',$statement);$statement=trim($statement);if($statement!=='')$pdo->exec($statement);}
 $pdo->beginTransaction();
 $pdo->prepare('INSERT INTO users(username,display_name,password_hash,role) VALUES (?,?,?,?)')->execute([$admin,$display,password_hash($pass,PASSWORD_DEFAULT),'admin']);
 $defaults=[
  'brand_name'=>'MEGA PRIME IMPORT','hero_kicker'=>'Cubiertas · Acabados · Maquinarias · Herramientas',
  'hero_title'=>'Materiales que transforman tus proyectos','hero_text'=>'Descubre cubiertas y acabados para tu obra. Solicita una proforma con las medidas y presentaciones que necesitas.',
  'hero_image'=>'','primary_cta_text'=>'Explorar productos',
  'nav_categories'=>'Categorías','nav_products'=>'Productos','nav_contact'=>'Contacto',
  'categories_kicker'=>'Encuentra lo que necesitas','categories_title'=>'Nuestras categorías',
  'products_kicker'=>'Productos y presentaciones','products_title'=>'Catálogo de materiales',
  'contact_kicker'=>'Atención personalizada','contact_title'=>'Construyamos tu próxima obra',
  'contact_text'=>'Selecciona los materiales en el carrito y solicita una proforma. Confirmaremos presentaciones, cantidades y valores antes de cualquier compra.','footer_text'=>'Mega Prime Import · Soluciones para construcción en Ecuador.',
  'contact_address'=>'Ecuador','contact_phone'=>'','contact_email'=>'','whatsapp'=>'',
  'social_facebook'=>'','social_instagram'=>'','social_tiktok'=>'',
  'site_notice'=>'Los valores de referencia están sujetos a confirmación al emitir la proforma. No se procesa pago en línea.',
  'privacy_notice'=>'Los datos de contacto se usan exclusivamente para gestionar y responder tu solicitud comercial. Para consultas sobre tus datos, comunícate mediante los datos de contacto publicados en este sitio. Antes de publicar, la empresa debe revisar y completar su aviso de privacidad.',
  'demo_mode'=>$demo?'1':'0'
 ];
 $put=$pdo->prepare('INSERT INTO site_settings(setting_key,setting_value) VALUES (?,?)');foreach($defaults as $k=>$v)$put->execute([$k,$v]);
 $category=$pdo->prepare('INSERT INTO categories(name,description,sort_order,visible) VALUES (?,?,?,1)');
 $catIds=[];foreach(['Cubiertas','Acabados de construcción','Herramientas','Maquinarias'] as $i=>$cat){$category->execute([$cat,'Consulta nuestras opciones de '.strtolower($cat).'.',$i+1]);$catIds[]=(int)$pdo->lastInsertId();}
 // Muestras optativas, SIN inventar precios. Marcadas explícitamente como demostración.
 if($demo){
  $examples=[
   [0,'Teja española PVC','TEJA-ESP','Cubierta PVC estilo español. Ficha y precio pendientes de confirmación.',['3.066 m','4.599 m','5.475 m','6.132 m']],
   [0,'Teja Roma PVC','TEJA-ROMA','Cubierta PVC estilo Roma. Formatos sujetos a confirmación.',['3.608 m','4.264 m','5.248 m']],
   [0,'Teja trapezoidal PVC','TEJA-TRAP','Cubierta PVC trapezoidal. Consulta largos, colores y espesores.',['Formato por consultar']],
   [0,'Policarbonato','POLI','Planchas de policarbonato. Consulta disponibilidad y espesores.',['Presentación por confirmar']],
   [0,'Duratecho metálico','DURATECHO','Cubierta metálica para construcción. Consulta medidas y acabados.',['Presentación por confirmar']],
   [1,'Cielo raso PVC','CIELO-PVC','Paneles para cielo raso PVC. Consulta formato vigente.',['Ancho útil 0.25 m','Ancho útil 0.30 m']],
   [1,'Wall Panel','WALL','Revestimiento decorativo interior. Consulta colores y perfiles.',['Presentación por confirmar']],
   [1,'Piso SPC','SPC','Pisos SPC para interiores. Consulta colores y dimensiones.',['Presentación por confirmar']],
   [1,'Alucobond','ALU','Panel compuesto para fachadas. Consulta espesores y medidas.',['Presentación por confirmar']],
   [2,'Herramientas de instalación','HERR','Accesorios y herramientas para instalación de cubiertas.',['Presentación por confirmar']],
   [3,'Equipos para construcción','MAQ','Equipos y maquinaria de construcción: disponibilidad por consultar.',['Presentación por confirmar']]
  ];
  $add=$pdo->prepare("INSERT INTO products(category_id,name,sku,description,base_price,unit,variants_json,visible,featured,sort_order) VALUES (?,?,?,?,0,'unidad',?,1,?,?)");
  foreach($examples as $i=>$e){$variants=json_encode(array_map(static fn($v)=>['label'=>$v,'price'=>0],$e[4]),JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);$add->execute([$catIds[$e[0]],$e[1],$e[2],$e[3],$variants,$i<3?1:0,$i+1]);}
 }
 $pdo->commit();
 $cfg=['db'=>['host'=>$host,'port'=>(int)$port,'name'=>$name,'user'=>$dbUser,'password'=>$dbPass],
  'app_key'=>bin2hex(random_bytes(32)),'allow_local_http'=>$local];
 umask(0077);$h=fopen($target,'x');if(!$h)throw new RuntimeException('No se pudo crear config.php');
 fwrite($h,"<?php\nreturn ".var_export($cfg,true).";\n");fclose($h);@chmod($target,0600);
 fwrite(STDOUT,"Instalación inicial lista. Verifique propietario/permisos y HTTPS antes de abrir al público.\n");
}catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();fwrite(STDERR,"La instalación no finalizó: ".$e->getMessage().". Revise el esquema en la base dedicada.\n");exit(1);}
