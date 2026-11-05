<?php
declare(strict_types=1);
/** Puente común para cada aplicación. Se aloja en carpeta privada; nunca contiene claves en el navegador. */
function mpi_sso_config():array {
 static $c=null;
 if($c===null){$p=__DIR__.'/sso.config.php';$v=is_file($p)?require $p:[];$c=is_array($v)?$v:[];}
 return $c;
}
function mpi_sso_enabled():bool {return (mpi_sso_config()['enabled']??false)===true;}
function mpi_sso_authorize_url():string {
 if(session_status()!==PHP_SESSION_ACTIVE)throw new RuntimeException('La sesión local no está iniciada');
 $c=mpi_sso_config();
 $state=bin2hex(random_bytes(32));$verifier=rtrim(strtr(base64_encode(random_bytes(48)),'+/','-_'),'=');
 $challenge=rtrim(strtr(base64_encode(hash('sha256',$verifier,true)),'+/','-_'),'=');
 $_SESSION['sso_pending']=['state'=>$state,'verifier'=>$verifier,'time'=>time()];
 return rtrim($c['auth_base_url'],'/').'/authorize.php?'.http_build_query(['client_id'=>$c['client_id'],'redirect_uri'=>$c['redirect_uri'],'state'=>$state,'code_challenge'=>$challenge],'','&',PHP_QUERY_RFC3986);
}
function mpi_sso_request(string $endpoint,array $args):array {
 $c=mpi_sso_config();$base=(string)($c['auth_base_url']??'');
 if(!preg_match('~^https://[a-zA-Z0-9.-]+(?::443)?$~',$base)&&!(($c['allow_local_http']??false)&&preg_match('~^http://(?:localhost|127\.0\.0\.1)(?::[0-9]+)?$~',$base)))throw new RuntimeException('El acceso central debe usar HTTPS y URL configurada');
 if(!function_exists('curl_init'))throw new RuntimeException('PHP cURL no está habilitado');
 $url=rtrim($base,'/').'/'.ltrim($endpoint,'/');
 $payload=array_merge($args,['client_id'=>$c['client_id'],'client_secret'=>$c['client_secret']]);
 $ch=curl_init($url);
 curl_setopt_array($ch,[CURLOPT_POST=>true,CURLOPT_POSTFIELDS=>http_build_query($payload,'','&',PHP_QUERY_RFC3986),CURLOPT_RETURNTRANSFER=>true,CURLOPT_CONNECTTIMEOUT=>3,CURLOPT_TIMEOUT=>7,CURLOPT_FOLLOWLOCATION=>false,CURLOPT_SSL_VERIFYPEER=>true,CURLOPT_SSL_VERIFYHOST=>2,CURLOPT_HTTPHEADER=>['Accept: application/json','Content-Type: application/x-www-form-urlencoded']]);
 $raw=curl_exec($ch);$status=(int)curl_getinfo($ch,CURLINFO_HTTP_CODE);$error=curl_error($ch);curl_close($ch);
 if($raw===false)throw new RuntimeException('Acceso central no disponible. '.$error);
 $r=json_decode($raw,true);
 if(!is_array($r)||$status<200||$status>=300)throw new RuntimeException('Acceso central rechazó la solicitud (HTTP '.$status.').');
 return $r;
}
function mpi_sso_exchange(string $code,string $state):array {
 if(session_status()!==PHP_SESSION_ACTIVE)throw new RuntimeException('Sesión no iniciada');
 $pending=$_SESSION['sso_pending']??null;unset($_SESSION['sso_pending']);
 if(!is_array($pending)||!preg_match('/^[a-f0-9]{64}$/D',$code)||!hash_equals((string)($pending['state']??''),$state)||time()-(int)($pending['time']??0)>300)throw new RuntimeException('Estado de autorización inválido o caducado');
 $c=mpi_sso_config();
 $r=mpi_sso_request('exchange.php',['redirect_uri'=>$c['redirect_uri'],'code'=>$code,'code_verifier'=>$pending['verifier']]);
 if(($r['active']??false)!==true||empty($r['user_id'])||empty($r['session_id'])||!preg_match('/^[a-f0-9]{64}$/D',(string)$r['session_id']))throw new RuntimeException('Respuesta de acceso inválida');
 return $r;
}
function mpi_sso_status(array $identity):?array {
 if(!mpi_sso_enabled())return null;
 $id=(int)($identity['id']??0);$sid=(string)($identity['sid']??'');
 if($id<1||!preg_match('/^[a-f0-9]{64}$/D',$sid))return null;
 try{$r=mpi_sso_request('status.php',['user_id'=>$id,'session_id'=>$sid]);return ($r['active']??false)===true&&(int)$r['user_id']===$id?$r:null;}
 catch(Throwable $e){error_log('SSO: status unavailable '.get_class($e));return null;}
}
function mpi_sso_revoke(array $identity):bool {
 if(!mpi_sso_enabled()||empty($identity['id'])||empty($identity['sid']))return false;
 try{$r=mpi_sso_request('revoke.php',['user_id'=>(int)$identity['id'],'session_id'=>(string)$identity['sid']]);return ($r['ok']??false)===true;}
 catch(Throwable $e){error_log('SSO: global logout revocation unavailable '.get_class($e));return false;}
}
