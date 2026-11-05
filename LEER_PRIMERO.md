# MEGA PRIME IMPORT · PLATAFORMA INTEGRADA V1.3

**Paquete limpio para instalar desde cero y realizar pruebas de aceptación.**

Incluye tres sitios independientes: tienda V93.11 con catálogo DEMO y calculadoras de materiales, centro de acceso unificado, y sistema de liquidación V85.1. Conserva logos de Mega Prime Importaciones y no incluye configuraciones privadas de producción.

Las cuentas se crean en el administrador de la tienda; el centro concede permisos para liquidaciones. **No se habilitan ventas reales mientras estén activos los datos ficticios.**

Comienza por `docs/DESPLIEGUE_DOCKER_COOLIFY.md`; para roles y permisos usa `docs/USUARIOS_Y_PERMISOS.md`. Las guías manuales y las evidencias de aceptación quedan fuera del repositorio de despliegue.

**Despliegue con Docker + Coolify + Cloudflare:** sigue `docs/DESPLIEGUE_DOCKER_COOLIFY.md`. Es la guía recomendada para producción; la instalación manual queda como referencia.

Los archivos dentro de `pruebas/resultados_v1_3` son evidencia local y no constituyen certificación de MariaDB o HTTPS de tu dominio.
