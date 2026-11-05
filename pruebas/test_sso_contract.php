<?php
declare(strict_types=1);
$code=file_get_contents(__DIR__.'/../integracion/sso_client.php');
$tmp=sys_get_temp_dir().'/mpi_sso_test_'.bin2hex(random_bytes(5));mkdir($tmp);
file_put_contents($tmp.'/sso_client.php',$code);
file_put_contents($tmp.'/sso.config.php',"<?php return ['enabled'=>true,'auth_base_url'=>'https://acceso.megaprimeimport.com','client_id'=>'tienda','client_secret'=>'example_secret','redirect_uri'=>'https://megaprimeimport.com/admin/sso_callback.php'];");
require $tmp.'/sso_client.php';
$results=[];function tcheck(string $what,bool $pass):void {global $results;$results[$what]=$pass;echo ($pass?'PASS ':'FAIL ').$what."\n";}
try{
 session_id('mpitest'.bin2hex(random_bytes(8)));session_start();
 tcheck('SSO habilitado en configuración privada',mpi_sso_enabled());
 $url=mpi_sso_authorize_url();$query=[];parse_str((string)parse_url($url,PHP_URL_QUERY),$query);
 tcheck('Authorization usa dominio HTTPS',str_starts_with($url,'https://acceso.megaprimeimport.com/authorize.php?'));
 tcheck('Cliente y callback permitidos',($query['client_id']??'')==='tienda'&&($query['redirect_uri']??'')==='https://megaprimeimport.com/admin/sso_callback.php');
 $pending=$_SESSION['sso_pending']??[];
 tcheck('State aleatorio 64 hex',preg_match('/^[a-f0-9]{64}$/D',(string)($query['state']??''))===1);
 tcheck('Verifier secreto almacenado solo en sesión',strlen((string)($pending['verifier']??''))>=43&&!str_contains($url,(string)($pending['verifier']??'')));
 $pkce=rtrim(strtr(base64_encode(hash('sha256',$pending['verifier'],true)),'+/','-_'),'=');
 tcheck('Desafío PKCE S256 consistente',$pkce===($query['code_challenge']??''));
 $bad=false;try{mpi_sso_exchange(str_repeat('a',64),str_repeat('0',64));}catch(RuntimeException $e){$bad=true;}
 tcheck('Callback rechaza state incorrecto sin enviar solicitud',$bad);
 tcheck('Intento fallido consume state',!isset($_SESSION['sso_pending']));
 tcheck('Estado inválido denegado sin servicio remoto',mpi_sso_status(['id'=>0,'sid'=>''])===null);
 session_destroy();
}finally{unlink($tmp.'/sso_client.php');unlink($tmp.'/sso.config.php');rmdir($tmp);}
$failed=count(array_filter($results,static fn($x)=>!$x));echo 'SSO CONTRACT '.count($results).' pruebas; '.$failed." fallos\n";exit($failed?1:0);
