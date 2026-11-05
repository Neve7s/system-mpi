<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/app/bootstrap.php';
if($_SERVER['REQUEST_METHOD']!=='GET')jsonResponse(['error'=>'Método no permitido'],405);
sessionStart();if(!currentUser())jsonResponse(['error'=>'Inicia sesión'],401);
try{
 $rows=db()->query('SELECT revision,config_json,created_at FROM site_content_history ORDER BY revision DESC LIMIT 10')->fetchAll();
 $result=[];foreach($rows as $row){$result[]=['revision'=>(int)$row['revision'],'date'=>$row['created_at'],'config'=>jsonDecode($row['config_json'])];}
 jsonResponse(['history'=>$result]);
}catch(Throwable $e){error_log('CMS history: '.$e->getMessage());jsonResponse(['error'=>'Historial no disponible'],503);}
