#!/usr/bin/env bash
set -Eeuo pipefail

log() { printf '[mpi] %s\n' "$*"; }
fail() { printf '[mpi][ERROR] %s\n' "$*" >&2; exit 1; }

normalize_domain() {
    printf '%s' "$1" \
        | sed -E 's#^[a-zA-Z][a-zA-Z0-9+.-]*://##; s#/.*$##; s#/$##'
}

MPI_DOMAIN_TIENDA="$(normalize_domain "${MPI_DOMAIN_TIENDA:-megaprimeimport.com}")"
MPI_DOMAIN_ACCESO="$(normalize_domain "${MPI_DOMAIN_ACCESO:-acceso.megaprimeimport.com}")"
MPI_DOMAIN_LIQUIDACIONES="$(normalize_domain "${MPI_DOMAIN_LIQUIDACIONES:-liquidaciones.megaprimeimport.com}")"
export MPI_DOMAIN_TIENDA MPI_DOMAIN_ACCESO MPI_DOMAIN_LIQUIDACIONES

for domain in "$MPI_DOMAIN_TIENDA" "$MPI_DOMAIN_ACCESO" "$MPI_DOMAIN_LIQUIDACIONES"; do
    [ -n "$domain" ] || fail "Un dominio MPI_DOMAIN_* está vacío."
    case "$domain" in
        */*|*:*|*' '*) fail "Dominio inválido: '$domain'. Usa solo el nombre, sin https://, puerto ni ruta." ;;
    esac
done

export MPI_SEED_DEMO="${MPI_SEED_DEMO:-1}"
export MP_ALLOW_DEMO_PUBLIC="${MP_ALLOW_DEMO_PUBLIC:-$MPI_SEED_DEMO}"
export MP_PUBLIC_BASE_URL="${MP_PUBLIC_BASE_URL:-https://$MPI_DOMAIN_TIENDA}"
export MP_RESET_FROM="${MP_RESET_FROM:-}"
export MPI_ENABLE_SSO="${MPI_ENABLE_SSO:-1}"
export MPI_RUN_MIGRATIONS="${MPI_RUN_MIGRATIONS:-1}"
export MPI_SEED_ADMIN="${MPI_SEED_ADMIN:-1}"
export MPI_APP_ENV="${MPI_APP_ENV:-production}"
export MPI_DB_MODE="${MPI_DB_MODE:-auto}"
export MPI_DB_PREFIX="${MPI_DB_PREFIX:-mpi}"

log "Renderizando vhosts Apache..."
mkdir -p /etc/apache2/sites-enabled
rm -f \
    /etc/apache2/sites-enabled/000-default.conf \
    /etc/apache2/sites-enabled/010-tienda.conf \
    /etc/apache2/sites-enabled/020-acceso.conf \
    /etc/apache2/sites-enabled/030-liquidaciones.conf

envsubst '${MPI_DOMAIN_TIENDA}' \
    < /opt/mpi/apache/tienda.conf.template \
    > /etc/apache2/sites-enabled/010-tienda.conf
envsubst '${MPI_DOMAIN_ACCESO}' \
    < /opt/mpi/apache/acceso.conf.template \
    > /etc/apache2/sites-enabled/020-acceso.conf
envsubst '${MPI_DOMAIN_LIQUIDACIONES}' \
    < /opt/mpi/apache/liquidaciones.conf.template \
    > /etc/apache2/sites-enabled/030-liquidaciones.conf

# Copia las imágenes DEMO al volumen persistente sin sobrescribir archivos del usuario.
seed_uploads() {
    local src="$1" dst="$2"
    mkdir -p "$dst"
    if [ -d "$src" ]; then
        cp -a -n "$src"/. "$dst"/ 2>/dev/null || true
    fi
    chown -R www-data:www-data "$dst"
    find "$dst" -type d -exec chmod 0755 {} + 2>/dev/null || true
    find "$dst" -type f -exec chmod 0644 {} + 2>/dev/null || true
}

seed_uploads /opt/mpi-seed/tienda-uploads /var/www/mpi/tienda/public/uploads
seed_uploads /opt/mpi-seed/liquidaciones-uploads /var/www/mpi/liquidaciones/public/uploads

mkdir -p /var/lib/php/sessions /data
chown www-data:www-data /var/lib/php/sessions 2>/dev/null || true
chmod 1733 /var/lib/php/sessions 2>/dev/null || true
chmod 0700 /data 2>/dev/null || true

log "Inicializando MariaDB, migraciones y administrador..."
php /usr/local/bin/mpi-bootstrap.php

log "Validando configuración de Apache..."
apache2ctl configtest

log "Iniciando Apache..."
exec apache2-foreground
