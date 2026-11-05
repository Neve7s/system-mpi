<?php
// Copiar a config.local.php fuera de public; no subir claves a git.
return ['host'=>'127.0.0.1','port'=>'3306','name'=>'mega_prime_web_v92','user'=>'mpi_web_user','password'=>'REEMPLAZAR_CON_CLAVE_FUERTE'];

// Para recuperar contraseñas vía enlace por correo:
// MP_PUBLIC_BASE_URL=https://tu-dominio.com (URL pública exacta de /public, sin barra final)
// MP_RESET_FROM=no-responder@tu-dominio.com (correo validado y entrega mail() configurada en el servidor)
// Se configuran como VARIABLES DE ENTORNO, no en páginas HTML/JS.
