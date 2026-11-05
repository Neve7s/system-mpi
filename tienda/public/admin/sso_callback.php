<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
header('Cache-Control: no-store, private');sessionStart();
if(!mpi_sso_enabled()){http_response_code(404);exit('Acceso central no configurado');}
try{
 $r=mpi_sso_exchange((string)($_GET['code']??''),(string)($_GET['state']??''));
 $q=db()->prepare('SELECT id,email,username,role,active,auth_version FROM admin_users WHERE id=? LIMIT 1');$q->execute([(int)$r['user_id']]);$u=$q->fetch();
 if(!$u||(int)$u['active']!==1||(int)$u['auth_version']!==(int)$r['auth_version']||$r['role']!=='store')throw new RuntimeException('Cuenta sin acceso a la tienda');
 session_regenerate_id(true);
 $_SESSION['auth']=['id'=>(int)$u['id'],'email'=>$u['email'],'username'=>$u['username'],'role'=>$u['role'],'auth_version'=>(int)$u['auth_version'],'signed_in_at'=>time(),'last_seen_at'=>time()];
 $_SESSION['sso_identity']=['id'=>(int)$r['user_id'],'sid'=>$r['session_id']];
 header('Location: /admin/editor.php',true,303);exit;
}catch(Throwable $e){error_log('Store SSO callback: '.get_class($e));http_response_code(403);echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Acceso no autorizado</title><body><p>No fue posible completar el acceso a la tienda.</p><a href="/admin/">Intentar nuevamente</a></body></html>';}
