<?php
declare(strict_types=1);
// Prueba pura de permisos. No necesita MariaDB.
define('MPI_PANEL_AUTH',true);
require dirname(__DIR__).'/private/panel_actions.php';
$cases=[
 ['admin puede habilitar DEMO','admin','0','1','1'],
 ['admin puede quitar DEMO','admin','1',null,'0'],
 ['editor conserva DEMO al editar campos','editor','1',null,'1'],
 ['editor conserva modo normal al editar','editor','0',null,'0'],
 ['editor puede enviar mismo estado DEMO','editor','1','1','1'],
 ['editor puede enviar mismo estado no DEMO','editor','0','0','0'],
 ['editor no puede desactivar DEMO','editor','1','0','EXCEPTION'],
 ['editor no puede activar DEMO','editor','0','1','EXCEPTION'],
];
$pass=0;
foreach($cases as [$name,$role,$current,$post,$expected]){
 try { $actual=mpi_demo_mode_for_user($role,$current,$post); $ok=$actual===$expected; }
 catch(InvalidArgumentException $e){$ok=$expected==='EXCEPTION';}
 echo ($ok?'PASS':'FAIL').": $name\n";
 if($ok)$pass++;
}
echo "TOTAL $pass/".count($cases)."\n";
if($pass!==count($cases))exit(1);
