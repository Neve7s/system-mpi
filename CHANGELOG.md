# Changelog

## [Unreleased]

### Added

- Despliegue Docker con PHP 8.3 + Apache para los tres sitios (`Dockerfile`, `docker-compose.yml`).
- Inicialización automática: resolución de `MARIADB_URL`, creación/acceso a bases, configuración desde variables de entorno, primer administrador y contenido DEMO.
- Migraciones idempotentes con `schema_migrations` y carpeta `docker/migrations/` por alcance (`tienda`, `identidad`, `liquidaciones`, `all`).
- Volúmenes persistentes para uploads, clave de aplicación y sesiones.
- Guía completa de Coolify, Cloudflare, DNS, subdominios, backups y troubleshooting en `docs/DESPLIEGUE_DOCKER_COOLIFY.md`.
- CI de GitHub Actions: validación de Compose, build de imagen, lint PHP y sintaxis del entrypoint.
- Variante `docker-compose.three-services.yml` para Coolify con un dominio por servicio.

### Fixed

- `tienda/public/.htaccess`: `DirectoryIndex index.html index.php` para que `/admin/` resuelva el login.
- `liquidaciones/public/.htaccess` y ejemplos Apache/Nginx: se permite `sso_callback.php`, necesario para el SSO.
