<?php
declare(strict_types=1);require dirname(__DIR__).'/private/bootstrap.php';id_secure();
if($_SERVER['REQUEST_METHOD']!=='POST')id_json(['error'=>'method_not_allowed'],405);
$c=id_authenticated_client();$sid=(string)($_POST['session_id']??'');$uid=filter_var($_POST['user_id']??null,FILTER_VALIDATE_INT);
if(!$uid||!preg_match('/^[a-f0-9]{64}$/D',$sid))id_json(['active'=>false],401);
try{
 $u=id_session_valid($sid,(int)$uid);$grant=$u?id_grant((int)$uid,$c['id']):null;
 if(!$u||!$grant)id_json(['active'=>false],401);
 id_touch($sid);id_json(['active'=>true,'user_id'=>(int)$uid,'role'=>$grant,'auth_version'=>(int)$u['auth_version'],'email'=>$u['email']]);
}catch(Throwable $e){error_log('Identity status: '.get_class($e));id_json(['error'=>'server_error'],503);}
