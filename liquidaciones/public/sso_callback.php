<?php
declare(strict_types=1);
require dirname(__DIR__).'/private/bootstrap.php';mpi_require_transport();mpi_headers(true);mpi_session();
if(!mpi_sso_enabled()){http_response_code(404);exit('Acceso central no configurado');}
try{
 $r=mpi_sso_exchange((string)($_GET['code']??''),(string)($_GET['state']??''));
 if(!in_array($r['role'],['admin','analyst'],true))throw new RuntimeException('Rol no autorizado');
 $id=(int)$r['user_id'];$role=$r['role'];$pdo=mpi_db();$pdo->beginTransaction();
 try{
  $q=$pdo->prepare('SELECT u.id,u.username,u.role,u.active,u.credential_version FROM identity_user_links l JOIN users u ON u.id=l.local_user_id WHERE l.store_user_id=? FOR UPDATE');$q->execute([$id]);$local=$q->fetch();
  if(!$local){
    // Nunca adoptar automáticamente una cuenta histórica con contraseña: evitar secuestro de registros anteriores.
    $name='mpi_sso_'.$id;
    $hash=password_hash(bin2hex(random_bytes(48)),PASSWORD_DEFAULT);
    $q=$pdo->prepare('INSERT INTO users(username,display_name,password_hash,role,active) VALUES (?,?,?,?,1)');
    $q->execute([$name,mb_safe((string)$r['username'],120),$hash,$role]);$uid=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO identity_user_links(store_user_id,local_user_id) VALUES (?,?)')->execute([$id,$uid]);
    $local=['id'=>$uid,'role'=>$role,'active'=>1,'credential_version'=>1];
  }elseif(!(int)$local['active'])throw new RuntimeException('Cuenta financiera desactivada localmente');
  elseif($local['role']!==$role){
    $pdo->prepare('UPDATE users SET role=?,credential_version=credential_version+1 WHERE id=?')->execute([$role,(int)$local['id']]);
    $local['role']=$role;$local['credential_version']=(int)$local['credential_version']+1;
  }
  $pdo->commit();
 }catch(Throwable $e){if($pdo->inTransaction())$pdo->rollBack();throw $e;}
 session_regenerate_id(true);
 $_SESSION=['uid'=>(int)$local['id'],'ver'=>(string)$local['credential_version'],'last'=>time(),'csrf'=>bin2hex(random_bytes(32)),'sso_identity'=>['id'=>$id,'sid'=>$r['session_id']]];
 header('Location: /admin.php',true,303);exit;
}catch(Throwable $e){error_log('Liquid SSO callback: '.get_class($e));http_response_code(403);echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Acceso no autorizado</title><p>No se pudo completar el acceso a liquidaciones. Verifica los permisos y la base de datos.</p><a href="/admin.php">Volver</a></html>';}
