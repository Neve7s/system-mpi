<?php
// Guardar como config.php FUERA de public/. Generar secretos aleatorios de 32 bytes (hex de 64 caracteres).
return [
 'base_url'=>'https://acceso.megaprimeimport.com',
 'store_url'=>'https://megaprimeimport.com',
 'liquid_url'=>'https://liquidaciones.megaprimeimport.com',
 'require_https'=>true,
 'store_db'=>['host'=>'127.0.0.1','port'=>3306,'name'=>'mpi_tienda','user'=>'USUARIO_SOLO_AUTENTICACION','password'=>'CAMBIAR'],
 'identity_db'=>['host'=>'127.0.0.1','port'=>3306,'name'=>'mpi_identidad','user'=>'USUARIO_IDENTIDAD','password'=>'CAMBIAR'],
 'clients'=>[
  'tienda'=>['redirect_uri'=>'https://megaprimeimport.com/admin/sso_callback.php','secret'=>'REEMPLAZAR_CON_SECRETO_ALEATORIO_TIENDA'],
  'liquidaciones'=>['redirect_uri'=>'https://liquidaciones.megaprimeimport.com/sso_callback.php','secret'=>'REEMPLAZAR_CON_SECRETO_ALEATORIO_LIQUIDACIONES'],
 ],
];
