<?php
declare(strict_types=1);
require dirname(__DIR__).'/private/bootstrap.php';mpi_headers(true);mpi_require_transport();
header('Content-Type: application/json; charset=utf-8');
if($_SERVER['REQUEST_METHOD']!=='POST'){http_response_code(405);header('Allow: POST');exit('{"ok":false}');}
mpi_check_csrf();
$u=mpi_user();if(!$u){http_response_code(401);exit('{"ok":false}');}
if(($_POST['actividad']??'0')==='1'){$_SESSION['last']=time();}
echo '{"ok":true}';
