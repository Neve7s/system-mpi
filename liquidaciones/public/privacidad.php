<?php
declare(strict_types=1);
require dirname(__DIR__).'/private/bootstrap.php';
mpi_require_transport();mpi_headers();
$brand=mpi_setting('brand_name','Mega Prime Import');
$notice=mpi_setting('privacy_notice','La empresa utiliza los datos enviados en el formulario para responder solicitudes de proforma. Consulte el contacto comercial para ejercer sus derechos.');
$email=mpi_setting('contact_email','');
?><!doctype html><html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="theme-color" content="#091221"><title>Privacidad · <?=mpi_e($brand)?></title><link rel="stylesheet" href="assets/store.css"></head><body><header class="site-header"><nav class="nav wrap"><a class="brand" href="index.php"><?=mpi_e($brand)?></a><a class="btn outline" href="index.php">Volver a la tienda</a></nav></header><main class="privacy-content"><h1>Aviso de privacidad</h1><p><?=nl2br(mpi_e($notice))?></p><?php if(filter_var($email,FILTER_VALIDATE_EMAIL)):?><p>Contacto: <a href="mailto:<?=mpi_e($email)?>"><?=mpi_e($email)?></a></p><?php endif;?><p class="muted">El responsable de la página debe revisar y completar este aviso con su política de tratamiento y conservación de datos antes de recibir solicitudes reales.</p><a class="btn gold" href="index.php">Ir al catálogo</a></main></body></html>
