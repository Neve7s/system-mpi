<?php
/** Pruebas de validaciones puras; no requieren credenciales ni MariaDB. */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(403); exit; }
define('MPI_PANEL_AUTH', true);
require dirname(__DIR__).'/private/bootstrap.php';
require dirname(__DIR__).'/private/panel_actions.php';
$checks=0;$fails=[];
function check84(bool $ok,string $name):void {global $checks,$fails;$checks++;if(!$ok)$fails[]=$name;echo ($ok?'OK':'ERROR').' - '.$name.PHP_EOL;}
function throws84(callable $test):bool {try{$test();return false;}catch(InvalidArgumentException){return true;}}
check84(mpi_price('18.5000')==='18.5000','Precio decimal con 4 posiciones');
check84(mpi_price('0')==='0.0000','Precio a confirmar');
check84(throws84(fn()=>mpi_price('-1')),'Precio negativo rechazado');
check84(throws84(fn()=>mpi_price('1,50')),'Precio con coma rechazado');
check84(throws84(fn()=>mpi_price('1.12345')),'Más de cuatro decimales rechazado');
check84(throws84(fn()=>mpi_price('999999999')),'Precio fuera de rango rechazado');
$v=json_decode(mpi_variants("4.60 m | 19.5000\n5.80 m | 0"),true);
check84(count($v)===2 && $v[0]['label']==='4.60 m' && $v[0]['price']===19.5,'Presentaciones y precios interpretados');
check84(throws84(fn()=>mpi_variants('3.50 m sin separador')),'Presentación mal formada rechazada');
check84(throws84(fn()=>mpi_variants('3.50 m | -9')),'Variante con importe negativo rechazada');
check84(mpi_valid_url('https://ejemplo.com/perfil') && !mpi_valid_url('javascript:alert(1)'),'Enlaces externos HTTPS válidos');
check84(!mpi_valid_url('https://user:password@ejemplo.com'),'Enlace con credenciales rechazado');
check84(mpi_image('uploads/'.str_repeat('a',32).'.webp')!=='','Nombre de imagen generada admitido');
check84(mpi_image('uploads/peligro.php')==='','Fichero no autorizado no sirve como imagen');
check84(mpi_e('<script>')==='&lt;script&gt;','Escapado HTML');
echo "\nRESULTADO ".($checks-count($fails))."/$checks".PHP_EOL;
if($fails){fwrite(STDERR,implode('; ',$fails).PHP_EOL);exit(1);}
