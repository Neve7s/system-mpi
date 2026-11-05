# Mega Prime Import · Plataforma Integrada V1.3

Tres aplicaciones PHP 8.2+ sobre MariaDB, empaquetadas en un solo contenedor Docker:

| Sitio | Dominio por defecto | DocumentRoot |
|---|---|---|
| Tienda y CMS | `megaprimeimport.com` | `tienda/public` |
| Centro de acceso SSO | `acceso.megaprimeimport.com` | `acceso/public` |
| Liquidaciones | `liquidaciones.megaprimeimport.com` | `liquidaciones/public` |

La tienda incluye catálogo DEMO, calculadoras de materiales, carrito y CMS. El centro de acceso es el proveedor SSO y el panel de permisos financieros. Liquidaciones conserva su propia base y sus exportaciones Excel/PDF.

> **Modo DEMO:** por defecto el primer arranque carga 27 productos ficticios. No habilites ventas reales hasta reemplazar datos, precios, fotos, contactos y avisos legales. Para producción real: `MPI_SEED_DEMO=0` y `MP_ALLOW_DEMO_PUBLIC=0`.

## Inicio rápido con Docker

```sh
cp .env.example .env
# Edita .env: MARIADB_URL (debe apuntar a tu MariaDB existente), dominios y MPI_ADMIN_EMAIL
docker compose up -d --build
docker compose logs -f web
```

Para una prueba local con MariaDB incluida usa `docker-compose.test.yml`:

```sh
docker compose -f docker-compose.test.yml up -d --build
curl -H "Host: tienda.test" http://127.0.0.1:8080/
```

El compose principal **no** levanta MariaDB: usa la base existente que indiques en `MARIADB_URL`. La guía completa explica DNS, Cloudflare, Coolify, migraciones, backups y troubleshooting:

**[docs/DESPLIEGUE_DOCKER_COOLIFY.md](docs/DESPLIEGUE_DOCKER_COOLIFY.md)**

## Qué hace el contenedor al arrancar

1. Renderiza los tres `VirtualHost` de Apache con los dominios configurados.
2. Resuelve `MARIADB_URL` y crea/accede a las bases (tres separadas o una sola).
3. Genera `config.local.php`, `config.php` y `sso.config.php` desde variables de entorno.
4. Aplica los esquemas base y las migraciones pendientes de `docker/migrations/`.
5. Crea el primer administrador solo si la tabla está vacía.
6. Carga el contenido DEMO solo si la base está vacía.
7. Arranca Apache.

Los redeploys son idempotentes: no duplican datos ni sobrescriben contenido. Las imágenes subidas y la clave de aplicación viven en volúmenes persistentes.

## Estructura del despliegue

```
Dockerfile
docker-compose.yml                 # Recomendado: 1 servicio, 3 dominios
docker-compose.three-services.yml  # Alternativa: 3 servicios, 1 dominio cada uno
docker/
  apache/        # Plantillas de VirtualHost
  php/           # php.ini de producción
  entrypoint.sh  # Arranque
  bootstrap.php  # MariaDB, migraciones, admin y DEMO
  migrations/    # Migraciones nuevas (tienda__/identidad__/liquidaciones__/all__)
```

## Documentación útil

- [Despliegue Docker + Coolify + Cloudflare](docs/DESPLIEGUE_DOCKER_COOLIFY.md)
- [Usuarios y permisos](docs/USUARIOS_Y_PERMISOS.md)
- [IA local de planos](docs/IA_LOCAL_V1_3.md)
- [Checklist de publicación](tienda/CHECKLIST_PUBLICACION_V93_11.md)
