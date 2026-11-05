<?php
declare(strict_types=1);require dirname(__DIR__).'/private/bootstrap.php';id_secure();id_session();
if($_SERVER['REQUEST_METHOD']==='POST'){
 id_check_csrf();
 if(!empty($_SESSION['identity']['sid']))id_db_identity()->prepare('UPDATE identity_sessions SET revoked_at=UTC_TIMESTAMP() WHERE session_id=?')->execute([$_SESSION['identity']['sid']]);
 $_SESSION=[];if(ini_get('session.use_cookies')){$p=session_get_cookie_params();setcookie(session_name(),'', ['expires'=>time()-3600,'path'=>'/','secure'=>(bool)$p['secure'],'httponly'=>true,'samesite'=>'Lax']);}session_destroy();
 header('Location: /index.php?logout=1',true,303);exit;
}
id_body('Cerrar sesión global');echo '<h1>Cerrar sesión</h1><p>Se revocará el acceso en tienda y liquidaciones asociado a este ingreso. Los formularios sin guardar se perderán.</p><form method="post"><input type="hidden" name="csrf" value="'.id_escape(id_csrf()).'"><button type="submit">Cerrar sesión en ambos sistemas</button></form><p><a href="/index.php">Cancelar</a></p>';id_end();
