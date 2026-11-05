<?php
// Sólo incluir desde public/panel.php tras autenticación + CSRF.
if(!defined('MPI_PANEL_AUTH')){http_response_code(403);exit;}
function mpi_field(string $key,int $max=500):string{
 $v=$_POST[$key]??'';
 if(!is_string($v))throw new InvalidArgumentException('Campo inválido: '.$key);
 return mb_safe(trim($v),$max);
}
function mpi_int(string $key,int $min=0,int $max=2000000000):int{
 $v=filter_var($_POST[$key]??null,FILTER_VALIDATE_INT);
 if($v===false||$v===null||$v<$min||$v>$max)throw new InvalidArgumentException('Número inválido: '.$key);
 return $v;
}
function mpi_price(string $value):string {
 if(!preg_match('/^\d{1,8}(?:\.\d{1,4})?$/D',$value)||!is_finite((float)$value)||((float)$value)>99999999)throw new InvalidArgumentException('Precio inválido. Usa punto decimal y hasta 4 decimales.');
 return number_format((float)$value,4,'.','');
}
function mpi_variants(string $lines):string {
 if(strlen($lines)>4000)throw new InvalidArgumentException('Demasiadas presentaciones');
 $out=[];foreach(preg_split('/\r?\n/',trim($lines)) as $line){$line=trim($line);if($line==='')continue;
  $parts=explode('|',$line);if(count($parts)!==2)throw new InvalidArgumentException('Cada variante debe ser Presentación | Precio.');
  $label=trim($parts[0]);if(strlen($label)<2||strlen($label)>160)throw new InvalidArgumentException('Nombre de variante inválido');
  $out[]=['label'=>$label,'price'=>(float)mpi_price(trim($parts[1]))];if(count($out)>30)throw new InvalidArgumentException('Máximo 30 presentaciones.');
 }
 return json_encode($out,JSON_UNESCAPED_UNICODE|JSON_THROW_ON_ERROR);
}
function mpi_upload(string $field,string $existing=''):string{
 if(empty($_FILES[$field])||($_FILES[$field]['error']??UPLOAD_ERR_NO_FILE)===UPLOAD_ERR_NO_FILE)return $existing;
 $f=$_FILES[$field];if(is_array($f['name']))throw new InvalidArgumentException('Una imagen por campo.');
 if($f['error']!==UPLOAD_ERR_OK||$f['size']<1||$f['size']>5*1024*1024||!is_uploaded_file($f['tmp_name']))throw new InvalidArgumentException('Imagen no válida. Máximo 5 MB.');
 $mime=(new finfo(FILEINFO_MIME_TYPE))->file($f['tmp_name']);$types=['image/jpeg'=>'jpg','image/png'=>'png','image/webp'=>'webp'];
 $dimensions=@getimagesize($f['tmp_name']);
 if(!isset($types[$mime])||!$dimensions||$dimensions[0]<1||$dimensions[1]<1||$dimensions[0]>6000||$dimensions[1]>6000)throw new InvalidArgumentException('Solo imágenes JPG, PNG o WebP válidas (máximo 6000 × 6000 píxeles).');
 $dir=MPI_ROOT.'/public/uploads';if(!is_dir($dir)||!is_writable($dir))throw new RuntimeException('La carpeta public/uploads no tiene permisos seguros de escritura.');
 $name=bin2hex(random_bytes(16)).'.'.$types[$mime];$dst=$dir.'/'.$name;
 if(!move_uploaded_file($f['tmp_name'],$dst))throw new RuntimeException('No se pudo almacenar la imagen.');
 @chmod($dst,0644);return 'uploads/'.$name;
}
function mpi_demo_mode_for_user(string $role,string $current,$posted):string {
 if(!in_array($current,['0','1'],true))throw new InvalidArgumentException('Modo DEMO incorrecto.');
 $requested=($role!=='admin' && $posted===null)?$current:(!empty($posted)?'1':'0');
 if($role!=='admin' && $requested!==$current)throw new InvalidArgumentException('Solo el administrador puede modificar el modo DEMO.');
 return $role==='admin'?$requested:$current;
}
function mpi_save_site(array $me):string{
 $fields=['brand_name'=>100,'hero_kicker'=>160,'hero_title'=>180,'hero_text'=>700,'primary_cta_text'=>65,
  'nav_categories'=>65,'nav_products'=>65,'nav_contact'=>65,
  'categories_kicker'=>160,'categories_title'=>160,'products_kicker'=>160,'products_title'=>160,'contact_kicker'=>160,'contact_title'=>160,'contact_text'=>700,'footer_text'=>500,'contact_address'=>300,'contact_phone'=>30,'contact_email'=>180,'whatsapp'=>15,'social_facebook'=>500,'social_instagram'=>500,'social_tiktok'=>500,'site_notice'=>700,'privacy_notice'=>1500];
 $values=[];foreach($fields as $k=>$n)$values[$k]=mpi_field($k,$n);
 if($values['brand_name']===''||$values['hero_title']==='')throw new InvalidArgumentException('La marca y el título de portada son obligatorios.');
 if($values['contact_email']!==''&&!filter_var($values['contact_email'],FILTER_VALIDATE_EMAIL))throw new InvalidArgumentException('Correo de contacto inválido.');
 if($values['whatsapp']!==''&&!preg_match('/^[0-9]{8,15}$/',$values['whatsapp']))throw new InvalidArgumentException('WhatsApp: código de país + número, solo dígitos.');
 foreach(['social_facebook','social_instagram','social_tiktok'] as $k)if(!mpi_valid_url($values[$k]))throw new InvalidArgumentException('Red social debe empezar con https://');
 $values['demo_mode']=mpi_demo_mode_for_user($me['role'],mpi_setting('demo_mode','0'),$_POST['demo_mode']??null);
 $values['hero_image']=mpi_upload('hero_image',mpi_setting('hero_image'));
 if(!empty($_POST['clear_hero_image']))$values['hero_image']='';
 $s=mpi_db()->prepare('INSERT INTO site_settings(setting_key,setting_value) VALUES (?,?) ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)');
 foreach($values as $k=>$v)$s->execute([$k,$v]);return 'Portada, cabecera y pie guardados.';
}
function mpi_save_category():string{
 $id=mpi_int('id',0);$name=mpi_field('name',120);$description=mpi_field('description',1000);$sort=mpi_int('sort_order',0,100000);$visible=!empty($_POST['visible'])?1:0;
 if($name==='')throw new InvalidArgumentException('Escribe el nombre de la categoría.');
 $pdo=mpi_db();$previous='';if($id){$s=$pdo->prepare('SELECT image_path FROM categories WHERE id=?');$s->execute([$id]);$previous=$s->fetchColumn();if($previous===false)throw new InvalidArgumentException('Categoría no encontrada.');}
 $image=mpi_upload('category_image',(string)$previous);if(!empty($_POST['clear_image']))$image='';
 if($id){$pdo->prepare('UPDATE categories SET name=?,description=?,sort_order=?,visible=?,image_path=? WHERE id=?')->execute([$name,$description,$sort,$visible,$image,$id]);}
 else{$pdo->prepare('INSERT INTO categories(name,description,sort_order,visible,image_path) VALUES (?,?,?,?,?)')->execute([$name,$description,$sort,$visible,$image]);}
 return 'Categoría guardada.';
}
function mpi_save_product():string{
 $id=mpi_int('id',0);$categoryId=mpi_int('category_id',1);$name=mpi_field('name',180);$sku=mpi_field('sku',80);$description=mpi_field('description',2500);$unit=mpi_field('unit',30);$sort=mpi_int('sort_order',0,100000);
 $price=mpi_price(mpi_field('base_price',25));$variants=mpi_variants(mpi_field('variants_lines',4000));$visible=!empty($_POST['visible'])?1:0;$featured=!empty($_POST['featured'])?1:0;
 if($name===''||$unit==='')throw new InvalidArgumentException('Producto y unidad son obligatorios.');
 $pdo=mpi_db();$s=$pdo->prepare('SELECT id FROM categories WHERE id=?');$s->execute([$categoryId]);if(!$s->fetchColumn())throw new InvalidArgumentException('Categoría inexistente.');
 $previous='';if($id){$s=$pdo->prepare('SELECT image_path FROM products WHERE id=?');$s->execute([$id]);$previous=$s->fetchColumn();if($previous===false)throw new InvalidArgumentException('Producto no encontrado.');}
 $image=mpi_upload('product_image',(string)$previous);if(!empty($_POST['clear_image']))$image='';
 if($id){$pdo->prepare('UPDATE products SET category_id=?,name=?,sku=?,description=?,base_price=?,unit=?,image_path=?,variants_json=?,visible=?,featured=?,sort_order=? WHERE id=?')->execute([$categoryId,$name,$sku,$description,$price,$unit,$image,$variants,$visible,$featured,$sort,$id]);}
 else{$pdo->prepare('INSERT INTO products(category_id,name,sku,description,base_price,unit,image_path,variants_json,visible,featured,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,?)')->execute([$categoryId,$name,$sku,$description,$price,$unit,$image,$variants,$visible,$featured,$sort]);}
 return 'Producto y sus presentaciones guardados.';
}
function mpi_user_manage(array $me):string{
 if(mpi_sso_enabled())throw new InvalidArgumentException('Las cuentas se administran únicamente desde la tienda.');
 if($me['role']!=='admin')throw new RuntimeException('Solo el administrador gestiona cuentas.');
 $op=mpi_field('action',30);$pdo=mpi_db();
 if($op==='user_add'){
  $username=mpi_field('username',100);$name=mpi_field('display_name',120);$role=mpi_field('role',20);$password=(string)($_POST['password']??'');
  if(!preg_match('/^[a-zA-Z0-9_.@-]{3,100}$/',$username)||strlen($name)<2||!in_array($role,['admin','editor','analyst'],true)||strlen($password)<12||strlen($password)>72)throw new InvalidArgumentException('Cuenta inválida o contraseña demasiado corta.');
  $pdo->prepare('INSERT INTO users(username,display_name,password_hash,role) VALUES (?,?,?,?)')->execute([$username,$name,password_hash($password,PASSWORD_DEFAULT),$role]);return 'Usuario creado con permisos reales.';
 }
 $id=mpi_int('user_id',1);
 $s=$pdo->prepare('SELECT id,role,active,credential_version FROM users WHERE id=?');$s->execute([$id]);$target=$s->fetch();if(!$target)throw new InvalidArgumentException('Usuario no encontrado.');
 if($op==='user_reset'){
  $pass=(string)($_POST['password']??'');if(strlen($pass)<12||strlen($pass)>72)throw new InvalidArgumentException('La nueva contraseña requiere de 12 a 72 caracteres.');
  $pdo->prepare('UPDATE users SET password_hash=?,credential_version=credential_version+1 WHERE id=?')->execute([password_hash($pass,PASSWORD_DEFAULT),$id]);
  if((int)$me['id']===$id){$_SESSION['ver']=(string)((int)$target['credential_version']+1);}return 'Contraseña cambiada y sesiones anteriores invalidadas.';
 }
 if($op==='user_toggle'){
  $active=!(int)$target['active']?1:0;
  if((int)$target['id']===(int)$me['id'] && !$active)throw new InvalidArgumentException('No puedes desactivar tu propia cuenta.');
  if($target['role']==='admin' && !$active){$s=$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND active=1");if((int)$s->fetchColumn()<2)throw new InvalidArgumentException('Debe permanecer al menos un administrador activo.');}
  $pdo->prepare('UPDATE users SET active=?, credential_version=credential_version+1 WHERE id=?')->execute([$active,$id]);return 'Estado de la cuenta actualizado.';
 }
 if($op==='user_role'){
  $role=mpi_field('role',20);if(!in_array($role,['admin','editor','analyst'],true))throw new InvalidArgumentException('Rol inválido.');
  if((int)$target['id']===(int)$me['id'])throw new InvalidArgumentException('No puedes cambiar tu propio rol.');
  if($target['role']==='admin'&&$role!=='admin'&&(int)$target['active']===1){$s=$pdo->query("SELECT COUNT(*) FROM users WHERE role='admin' AND active=1");if((int)$s->fetchColumn()<2)throw new InvalidArgumentException('Debe quedar al menos un administrador.');}
  $pdo->prepare('UPDATE users SET role=?,credential_version=credential_version+1 WHERE id=?')->execute([$role,$id]);return 'Rol actualizado y sesiones anteriores invalidadas.';
 }
 throw new InvalidArgumentException('Acción no reconocida.');
}
function mpi_handle_action(array $me):string {
 if(mpi_sso_enabled())throw new InvalidArgumentException('La tienda comercial y las cuentas se administran exclusivamente en el panel V93.11.');
 $action=mpi_field('action',30);$pdo=mpi_db();
 if(in_array($action,['user_add','user_reset','user_toggle','user_role'],true))$message=mpi_user_manage($me);
 elseif($action==='site_save')$message=mpi_save_site($me);
 elseif($action==='category_save')$message=mpi_save_category();
 elseif($action==='product_save')$message=mpi_save_product();
 elseif($action==='quote_status'){
  $id=mpi_int('quote_id',1);$status=mpi_field('status',20);if(!in_array($status,['nueva','contactado','cerrada'],true))throw new InvalidArgumentException('Estado inválido');
  $s=$pdo->prepare('SELECT id FROM quotes WHERE id=?');$s->execute([$id]);if(!$s->fetchColumn())throw new InvalidArgumentException('Proforma no encontrada.');
  $s=$pdo->prepare('UPDATE quotes SET status=? WHERE id=?');$s->execute([$status,$id]);$message='Estado guardado.';
 }else throw new InvalidArgumentException('Acción desconocida.');
 mpi_audit((int)$me['id'],$action,(string)($_POST['id']??$_POST['quote_id']??$_POST['user_id']??''));
 return $message;
}
