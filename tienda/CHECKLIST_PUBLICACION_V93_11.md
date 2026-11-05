# Lista de verificación de publicación — V93.11

Marcar sobre el **servidor real**, no sobre el navegador local. No contiene credenciales.

- [ ] La raíz del dominio apunta a `public/` y `app/`, `cli/`, `tests/` y `schema.sql` NO son accesibles mediante URL.
- [ ] HTTPS válido, certificado vigente, HSTS y redirección HTTP→HTTPS comprobados.
- [ ] `pdo_mysql`, `fileinfo`, PHP y MariaDB operativos; `php cli/preflight.php` sin fallos críticos.
- [ ] Base DEMO nueva confirmada; copia de seguridad real del servidor probada por restauración.
- [ ] El catálogo de prueba tiene 27 productos, 8 categorías y 36 fotografías después de cargar la muestra.
- [ ] Crear, editar, ocultar y recuperar categoría/producto/variante/foto desde admin, sin modificar HTML; persistencia tras cerrar sesión y recargar otro dispositivo.
- [ ] Menú, portada, imágenes, textos de servicios, WhatsApp, redes sociales y pie se actualizan en la web después de guardar.
- [ ] Admin: iniciar sesión, crear/desactivar usuarios, cambiar rol/contraseña, recuperar acceso por correo, cerrar sesión; sesión anterior ya no funciona.
- [ ] Editor comercial NO puede modificar reglas técnicas, paleta PDF, DEMO ni indexación; administrador sí puede.
- [ ] Público NO puede acceder al CMS editor, historial, cuentas, archivos privados ni endpoints de subida sin autenticación.
- [ ] La tienda se visualiza correctamente en móvil Android, iPhone/iPad, Windows/macOS y varios navegadores (prueba manual real).
- [ ] Carrito alineado a derecha en móvil, sin scroll horizontal; cotización de varios ítems y accesorios correcta.
- [ ] PDF A4 y A5 muestran león, encabezado azul/blanco/dorado y textos legibles; paleta editada por administrador se refleja después de guardar.
- [ ] Calculadoras: teja PVC, Roma, cielo raso PVC, piso SPC, accesorios y varias aguas comparados contra mediciones y resultados conocidos.
- [ ] IA navegador: detección compatible o ruta OCR/manual; lectura de PDF real con cotas conocidas, confirmación humana y sin envíos a API de pago.
- [ ] Datos ficticios y precios DEMO reemplazados antes de abrir ventas reales, con fotos y contactos autorizados.
- [ ] `php cli/publication_gate.php` aprobado, modo DEMO desactivado solo tras resolver avisos, políticas legales y robots/SEO comprobados.
- [ ] Confirmación final del responsable comercial y del técnico que revisó los cálculos y la seguridad.

**Criterio:** si falta una comprobación crítica, mantener acceso de pruebas. Ninguna prueba automatizada garantiza ausencia de fallos al 100%.
