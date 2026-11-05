<?php
declare(strict_types=1);
require dirname(__DIR__, 2).'/app/bootstrap.php';
sessionStart();
$user=currentUser();
if (!$user) { header('Location: ./index.php', true, 303); exit; }
if (($user['role']??'')!=='admin') {
    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store, private');
    echo '<!doctype html><html lang="es"><meta charset="utf-8"><title>Acceso denegado</title><body><h1>Acceso reservado al administrador</h1><a href="./editor.php">Volver</a></body></html>';
    exit;
}
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store, private');
header('X-Frame-Options: DENY');
$error='';
$success=(string)($_GET['ok']??'')==='1';

/** Esta operación es exclusiva del administrador y verifica de nuevo su contraseña. */
function verifyManagerPassword(PDO $db, int $actorId, string $password):void {
    $stmt=$db->prepare('SELECT password_hash,role,active FROM admin_users WHERE id=? FOR UPDATE');
    $stmt->execute([$actorId]);$actor=$stmt->fetch();
    if(!$actor || (int)$actor['active']!==1 || $actor['role']!=='admin' || !password_verify($password,(string)$actor['password_hash'])) {
        throw new RuntimeException('No se pudo confirmar la contraseña del administrador.');
    }
}
function userRowForUpdate(PDO $db,int $id):array {
    $stmt=$db->prepare('SELECT id,username,email,role,active FROM admin_users WHERE id=? FOR UPDATE');
    $stmt->execute([$id]);$row=$stmt->fetch();
    if(!$row)throw new RuntimeException('No se encontró el usuario.');
    return $row;
}
/** Evita desactivar o degradar la última cuenta de administrador activa, incluso entre peticiones simultáneas. */
function requireOtherActiveAdmin(PDO $db,int $targetId):void {
    $rows=$db->query("SELECT id FROM admin_users WHERE role='admin' AND active=1 ORDER BY id FOR UPDATE")->fetchAll(PDO::FETCH_COLUMN);
    if(count($rows)<=1 && in_array($targetId,array_map('intval',$rows),true)) {
        throw new RuntimeException('No se puede desactivar ni degradar la última cuenta administradora activa.');
    }
}
if($_SERVER['REQUEST_METHOD']==='POST') {
    try {
        checkCsrf();
        $action=(string)($_POST['action']??'');
        $managerPassword=(string)($_POST['manager_password']??'');
        if($managerPassword==='' || strlen($managerPassword)>1024)throw new RuntimeException('Confirma tu contraseña administrativa.');
        $pdo=db();$pdo->beginTransaction();
        try {
            verifyManagerPassword($pdo,(int)$user['id'],$managerPassword);
            if($action==='create') {
                $name=normalizeAdminUsername((string)($_POST['username']??''));
                $email=strtolower(trim((string)($_POST['email']??'')));
                $password=(string)($_POST['password']??'');
                $confirm=(string)($_POST['confirm_password']??'');
                $role=(string)($_POST['role']??'editor');
                if(!preg_match('/^[a-z0-9._-]{4,60}$/',$name))throw new RuntimeException('El nombre de usuario debe tener entre 4 y 60 caracteres.');
                if(!filter_var($email,FILTER_VALIDATE_EMAIL)||strlen($email)>190)throw new RuntimeException('Introduce un correo válido.');
                if(!in_array($role,['admin','editor'],true))throw new RuntimeException('Rol inválido.');
                if($password!==$confirm||strlen($password)<12||strlen($password)>1024)throw new RuntimeException('La contraseña debe coincidir y tener 12 caracteres como mínimo.');
                $stmt=$pdo->prepare('INSERT INTO admin_users(username,email,password_hash,role,active,auth_version) VALUES(?,?,?,?,1,1)');
                $stmt->execute([$name,$email,password_hash($password,PASSWORD_DEFAULT),$role]);
            } elseif($action==='update') {
                $targetId=filter_var($_POST['user_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
                if(!$targetId)throw new RuntimeException('Identificador de usuario inválido.');
                if($targetId===(int)$user['id'])throw new RuntimeException('Para modificar tu propia cuenta, utiliza «Mi cuenta».');
                $target=userRowForUpdate($pdo,(int)$targetId);
                $role=(string)($_POST['role']??'');
                $active=(string)($_POST['active']??'');
                if(!in_array($role,['admin','editor'],true)||!in_array($active,['0','1'],true))throw new RuntimeException('Rol o estado inválido.');
                if($target['role']==='admin' && (int)$target['active']===1 && ($role!=='admin'||$active==='0'))requireOtherActiveAdmin($pdo,(int)$targetId);
                $stmt=$pdo->prepare('UPDATE admin_users SET role=?, active=?, auth_version=auth_version+1 WHERE id=?');
                $stmt->execute([$role,(int)$active,(int)$targetId]);
                $stmt=$pdo->prepare('UPDATE admin_password_resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL');
                $stmt->execute([(int)$targetId]);
            } elseif($action==='reset') {
                $targetId=filter_var($_POST['user_id']??null,FILTER_VALIDATE_INT,['options'=>['min_range'=>1]]);
                if(!$targetId)throw new RuntimeException('Identificador de usuario inválido.');
                if($targetId===(int)$user['id'])throw new RuntimeException('Cambia tu propia contraseña desde «Mi cuenta».');
                userRowForUpdate($pdo,(int)$targetId);
                $newPass=(string)($_POST['new_password']??'');
                $confirm=(string)($_POST['confirm_password']??'');
                if($newPass!==$confirm||strlen($newPass)<12||strlen($newPass)>1024)throw new RuntimeException('La nueva contraseña debe coincidir y tener al menos 12 caracteres.');
                $stmt=$pdo->prepare('UPDATE admin_users SET password_hash=?, auth_version=auth_version+1 WHERE id=?');
                $stmt->execute([password_hash($newPass,PASSWORD_DEFAULT),(int)$targetId]);
                $stmt=$pdo->prepare('UPDATE admin_password_resets SET used_at=NOW() WHERE user_id=? AND used_at IS NULL');
                $stmt->execute([(int)$targetId]);
            } else throw new RuntimeException('Operación no permitida.');
            $pdo->commit();
            header('Location: ./users.php?ok=1',true,303);exit;
        } catch(Throwable $e) { if($pdo->inTransaction())$pdo->rollBack();throw $e; }
    } catch(PDOException $e) {
        error_log('User administration PDO error: '.$e->getCode());
        $error='No fue posible guardar. Revisa si el usuario o correo ya existen, y la conexión a MariaDB.';
    } catch(Throwable $e) {
        $error=$e instanceof RuntimeException?$e->getMessage():'No fue posible completar la operación.';
    }
}
$users=[];
try { $users=db()->query('SELECT id,username,email,role,active,created_at FROM admin_users ORDER BY id ASC')->fetchAll(); }
catch(Throwable $e){error_log('User administration listing error: '.get_class($e));$error='No se pudo consultar la lista. Comprueba MariaDB.';}
$csrf=csrfToken();
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex,nofollow"><title>Usuarios y permisos · Mega Prime Import</title>
<style>:root{color-scheme:dark}*{box-sizing:border-box}body{margin:0;padding:20px;background:#09131b;color:#f4f7f6;font:15px system-ui,Arial}main{max-width:1100px;margin:auto}h1,h2{color:#f1c65b}h1{font-size:clamp(1.4rem,3vw,2rem)}.lead{color:#c4d3d7;line-height:1.6}.nav{display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin:14px 0 20px}.nav a,.btn{background:#e7bd5b;color:#0c171d;border:0;border-radius:8px;padding:10px 14px;display:inline-block;font:700 14px system-ui;text-decoration:none;cursor:pointer}.nav a{background:#1b3544;color:#f7cf6a;border:1px solid #69747a}.pane{background:#142531;border:1px solid #657783;border-radius:14px;padding:20px;margin:20px 0}.formgrid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,210px),1fr));gap:12px}label{display:block;color:#dce6e6;font-weight:600}input,select{display:block;width:100%;margin-top:5px;padding:11px;background:#07161e;color:#fff;border:1px solid #738a92;border-radius:8px;font:inherit}input:focus-visible,select:focus-visible,.btn:focus-visible{outline:2px solid #f1c65b;outline-offset:2px}.hint{font-size:13px;color:#c5d5d8}.ok{color:#b8f2ca}.err{color:#ffc3c3}.user{border:1px solid #476472;border-radius:12px;margin:12px 0;padding:14px;background:#0b1c27}.user header{display:flex;justify-content:space-between;flex-wrap:wrap;gap:8px}.user strong{font-size:17px}.sub{color:#c5d4dc;word-break:break-word}.userform{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,170px),1fr));gap:12px;margin:12px 0}.badge{display:inline-block;border-radius:30px;padding:4px 9px;background:#26485a;font-size:13px}.badge.off{background:#633636;color:white}hr{border:0;border-top:1px solid #344e59;margin:14px 0}.self{color:#e9cb8a}.password{max-width:330px}</style></head><body><main><h1>Usuarios y permisos</h1><p class="lead">Crea cuentas de administrador o editor comercial; cambia sus permisos y contraseñas desde aquí, sin editar archivos. Para proteger tu tienda, cada cambio requiere verificar tu contraseña de administrador. Los cambios invalidan las sesiones anteriores de la cuenta afectada.</p><nav class="nav"><a href="./editor.php">Volver al administrador</a><a href="./account.php">Mi cuenta</a><a href="../index.html" target="_blank" rel="noopener noreferrer">Tienda web</a></nav>
<?php if($success):?><p class="ok" role="status">Cambio realizado correctamente.</p><?php endif;?><?php if($error):?><p class="err" role="alert"><?=h($error)?></p><?php endif;?>
<section class="pane"><h2>Crear usuario</h2><form method="post" autocomplete="off"><input type="hidden" name="action" value="create"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><div class="formgrid"><label>Usuario<input name="username" type="text" required minlength="4" maxlength="60" pattern="[a-z0-9._-]{4,60}"></label><label>Correo electrónico<input name="email" type="email" required maxlength="190"></label><label>Rol<select name="role"><option value="editor">Editor comercial</option><option value="admin">Administrador</option></select></label><label>Nueva contraseña<input name="password" type="password" autocomplete="new-password" minlength="12" required></label><label>Confirmar contraseña<input name="confirm_password" type="password" autocomplete="new-password" minlength="12" required></label><label>Tu contraseña de administrador<input name="manager_password" type="password" autocomplete="current-password" required></label></div><p class="hint">Los editores comerciales no pueden cambiar reglas matemáticas ni administrar cuentas. No se envían contraseñas por correo.</p><button class="btn" type="submit">Crear cuenta</button></form></section>
<section class="pane"><h2>Cuentas existentes (<?=count($users)?>)</h2><?php foreach($users as $target): $self=(int)$target['id']===(int)$user['id'];?><article class="user"><header><div><strong><?=h($target['username'])?></strong> <?= $self?'<span class="self">(tu cuenta)</span>':''?><div class="sub"><?=h($target['email'])?></div></div><div><span class="badge"><?=h($target['role']==='admin'?'Administrador':'Editor')?></span> <span class="badge <?=((int)$target['active']===1)?'':'off'?>"><?=((int)$target['active']===1)?'Activa':'Inactiva'?></span></div></header>
<?php if(!$self):?><form method="post" class="userform" autocomplete="off"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="update"><input type="hidden" name="user_id" value="<?=(int)$target['id']?>"><label>Permiso<select name="role"><option value="editor" <?=$target['role']==='editor'?'selected':''?>>Editor</option><option value="admin" <?=$target['role']==='admin'?'selected':''?>>Administrador</option></select></label><label>Estado<select name="active"><option value="1" <?=(int)$target['active']===1?'selected':''?>>Activa</option><option value="0" <?=(int)$target['active']!==1?'selected':''?>>Inactiva</option></select></label><label>Tu contraseña<input name="manager_password" type="password" autocomplete="current-password" required></label><div style="align-self:end"><button class="btn" type="submit">Guardar permisos</button></div></form><hr><form method="post" class="userform" autocomplete="off"><input type="hidden" name="csrf" value="<?=h($csrf)?>"><input type="hidden" name="action" value="reset"><input type="hidden" name="user_id" value="<?=(int)$target['id']?>"><label>Contraseña nueva<input name="new_password" type="password" autocomplete="new-password" minlength="12" required></label><label>Confirmar<input name="confirm_password" type="password" autocomplete="new-password" minlength="12" required></label><label>Tu contraseña<input name="manager_password" type="password" autocomplete="current-password" required></label><div style="align-self:end"><button class="btn" type="submit">Cambiar contraseña</button></div></form>
<?php else:?><p class="hint">Tu propia cuenta se edita desde «Mi cuenta» para evitar perder el acceso administrativo.</p><?php endif;?></article><?php endforeach;?></section><p class="hint">El administrador debe conservar al menos una cuenta administrativa activa. No utilices contraseñas de demostración en producción.</p></main></body></html>
