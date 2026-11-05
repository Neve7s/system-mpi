<?php
declare(strict_types=1);
require dirname(__DIR__).'/private/bootstrap.php';id_secure();id_session();
$c=(string)($_GET['client_id']??'');$redirect=(string)($_GET['redirect_uri']??'');$state=(string)($_GET['state']??'');$challenge=(string)($_GET['code_challenge']??'');
if(!id_validate_client($c,$redirect)||!preg_match('/^[a-f0-9]{64}$/D',$state)||!preg_match('/^[A-Za-z0-9_-]{43}$/D',$challenge)){http_response_code(400);exit('Solicitud de autorización inválida.');}
$u=id_user();
if(!$u){
 $_SESSION['pending_authorize']='/authorize.php?'.http_build_query(['client_id'=>$c,'redirect_uri'=>$redirect,'state'=>$state,'code_challenge'=>$challenge],'','&',PHP_QUERY_RFC3986);
 header('Location: /index.php',true,303);exit;
}
if(!id_grant((int)$u['id'],$c)){http_response_code(403);id_body('Permiso insuficiente');echo '<h1>Acceso denegado</h1><p>Esta cuenta no tiene permiso para esta aplicación.</p><a href="/index.php">Volver al centro</a>';id_end();exit;}
$code=bin2hex(random_bytes(32));$hash=hash('sha256',$code);
$q=id_db_identity()->prepare('INSERT INTO identity_codes(code_hash,client_id,redirect_uri,session_id,challenge,expires_at) VALUES (?,?,?,?,?,DATE_ADD(UTC_TIMESTAMP(),INTERVAL 90 SECOND))');
$q->execute([$hash,$c,$redirect,$_SESSION['identity']['sid'],$challenge]);
header('Location: '.$redirect.'?'.http_build_query(['code'=>$code,'state'=>$state],'','&',PHP_QUERY_RFC3986),true,303);exit;
