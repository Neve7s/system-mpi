<?php
declare(strict_types=1);require dirname(__DIR__).'/private/bootstrap.php';id_secure();id_session();
$me=id_user();if(!$me){header('Location: /index.php');exit;}
if($me['role']!=='admin'){http_response_code(403);exit('Se requiere ser administrador de la tienda.');}
$error='';$ok=false;
if($_SERVER['REQUEST_METHOD']==='POST'){
 id_check_csrf();
 try{
  $now=time();
  $window=(int)($_SESSION['grant_password_window']??0);
  if($now-$window>900){$_SESSION['grant_password_window']=$now;$_SESSION['grant_password_failures']=0;}
  if((int)($_SESSION['grant_password_failures']??0)>=5){http_response_code(429);header('Retry-After: 900');throw new RuntimeException('Demasiados intentos de confirmación. Intenta más tarde.');}
  $pass=(string)($_POST['manager_password']??'');
  if(strlen($pass)>1024||!password_verify($pass,$me['password_hash'])){
   $_SESSION['grant_password_failures']=(int)($_SESSION['grant_password_failures']??0)+1;
   throw new RuntimeException('Confirma tu contraseña de administrador.');
  }
  $_SESSION['grant_password_failures']=0;
  $uid=filter_var($_POST['user_id']??null,FILTER_VALIDATE_INT);
  $role=(string)($_POST['liquid_role']??'none');
  if(!$uid||!in_array($role,['none','admin','analyst'],true))throw new RuntimeException('Permiso inválido.');
  $target=id_get_user((int)$uid);if(!$target)throw new RuntimeException('Cuenta inexistente.');
  if($role!=='none' && (int)$target['active']!==1)throw new RuntimeException('No se pueden conceder permisos a cuentas desactivadas.');
  if($role==='admin'&&$target['role']!=='admin')throw new RuntimeException('Solo un administrador comercial puede administrar liquidaciones.');
  $q=id_db_identity()->prepare('INSERT INTO identity_grants(store_user_id,liquid_role,enabled) VALUES (?,?,?) ON DUPLICATE KEY UPDATE liquid_role=VALUES(liquid_role),enabled=VALUES(enabled)');
  $q->execute([(int)$uid,$role==='none'?null:$role,$role==='none'?0:1]);$ok=true;
 }catch(Throwable $e){$error=$e instanceof RuntimeException?$e->getMessage():'No se pudo guardar el permiso.';error_log('Grant edit: '.get_class($e));}
}
$users=id_store()->query('SELECT id,username,email,role,active FROM admin_users ORDER BY id')->fetchAll();$grants=id_db_identity()->query('SELECT store_user_id,liquid_role,enabled FROM identity_grants')->fetchAll(PDO::FETCH_UNIQUE|PDO::FETCH_ASSOC);
id_body('Permisos');echo '<h1>Permisos de liquidaciones</h1><p>Las cuentas y contraseñas se administran únicamente desde la tienda. Aquí defines quién puede utilizar el sistema privado de importaciones. Los permisos nuevos se validan desde el servidor.</p><nav><a href="/index.php">Centro de operaciones</a><a href="'.id_escape(rtrim(id_cfg()['store_url'],'/').'/admin/users.php').'">Crear o editar cuentas en la tienda</a></nav>';
if($ok)echo '<p>Permisos guardados.</p>';if($error)echo '<p class="error">'.id_escape($error).'</p>';
echo '<table><thead><tr><th>Usuario</th><th>Tienda</th><th>Liquidaciones</th><th>Modificar</th></tr></thead><tbody>';
foreach($users as $u){$grant=$grants[$u['id']]??null;$value=($grant&&$grant['enabled'])?$grant['liquid_role']:'none';
 echo '<tr><td>'.id_escape($u['username']).'<br><small>'.id_escape($u['email']).'</small></td><td>'.id_escape($u['role']).($u['active']?'':' (inactivo)').'</td><td>'.id_escape($value).'</td><td><form method="post"><input type="hidden" name="csrf" value="'.id_escape(id_csrf()).'"><input type="hidden" name="user_id" value="'.(int)$u['id'].'"><label>Permiso<select name="liquid_role"><option value="none"'.($value==='none'?' selected':'').'>Sin acceso</option><option value="analyst"'.($value==='analyst'?' selected':'').'>Analista</option>';
 if($u['role']==='admin')echo '<option value="admin"'.($value==='admin'?' selected':'').'>Administrador</option>';
 echo '</select></label><label>Tu contraseña<input type="password" name="manager_password" required autocomplete="current-password"></label><button type="submit">Guardar</button></form></td></tr>';
}
echo '</tbody></table>';id_end();
