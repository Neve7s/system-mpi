<?php
declare(strict_types=1);require dirname(__DIR__).'/private/bootstrap.php';id_secure();
if($_SERVER['REQUEST_METHOD']!=='POST')id_json(['error'=>'method_not_allowed'],405);
$auth=id_authenticated_client();$client=$auth['id'];$c=$auth['settings'];
$code=(string)($_POST['code']??'');$redirect=(string)($_POST['redirect_uri']??'');$verifier=(string)($_POST['code_verifier']??'');
if(!id_validate_client($client,$redirect)||!preg_match('/^[a-f0-9]{64}$/D',$code)||!preg_match('/^[A-Za-z0-9_-]{43,128}$/D',$verifier))id_json(['error'=>'invalid_request'],400);
$challenge=rtrim(strtr(base64_encode(hash('sha256',$verifier,true)),'+/','-_'),'=');
$db=id_db_identity();$db->beginTransaction();
try {
 $q=$db->prepare('SELECT * FROM identity_codes WHERE code_hash=? FOR UPDATE');$q->execute([hash('sha256',$code)]);$a=$q->fetch();
 if(!$a||$a['client_id']!==$client||!hash_equals((string)$a['redirect_uri'],$redirect)||!hash_equals((string)$a['challenge'],$challenge)||$a['used_at']!==null||strtotime($a['expires_at'].' UTC')<=time()){
  $db->rollBack();id_json(['error'=>'invalid_grant'],400);
 }
 $s=$db->prepare('SELECT store_user_id FROM identity_sessions WHERE session_id=?');$s->execute([$a['session_id']]);$uid=(int)$s->fetchColumn();
 $u=$uid?id_session_valid($a['session_id'],$uid):null;$grant=$u?id_grant($uid,$client):null;
 if(!$u||!$grant){$db->rollBack();id_json(['error'=>'access_denied'],403);}
 $db->prepare('UPDATE identity_codes SET used_at=UTC_TIMESTAMP() WHERE code_hash=?')->execute([hash('sha256',$code)]);
 $db->commit();id_touch((string)$a['session_id']);
 id_json(['active'=>true,'user_id'=>$uid,'username'=>$u['username'],'email'=>$u['email'],'role'=>$grant,'auth_version'=>(int)$u['auth_version'],'session_id'=>$a['session_id']],200);
}catch(Throwable $e){if($db->inTransaction())$db->rollBack();error_log('identity exchange: '.get_class($e));id_json(['error'=>'server_error'],503);}
