<?php
declare(strict_types=1);require dirname(__DIR__).'/private/bootstrap.php';id_secure();
if($_SERVER['REQUEST_METHOD']!=='POST')id_json(['error'=>'method_not_allowed'],405);
id_authenticated_client();$sid=(string)($_POST['session_id']??'');$uid=filter_var($_POST['user_id']??null,FILTER_VALIDATE_INT);
if(!$uid||!preg_match('/^[a-f0-9]{64}$/D',$sid))id_json(['error'=>'invalid_request'],400);
id_db_identity()->prepare('UPDATE identity_sessions SET revoked_at=UTC_TIMESTAMP() WHERE session_id=? AND store_user_id=? AND revoked_at IS NULL')->execute([$sid,(int)$uid]);
id_json(['ok'=>true]);
