#!/usr/bin/env bash
# Respaldo SQL + fotos. No modifica ni vacía tablas. EJECUTAR EN SERVIDOR CONTROLADO.
set -euo pipefail
umask 077
BASE="$(cd "$(dirname "$0")/.." && pwd)"
: "${MP_DB_HOST:?Definir MP_DB_HOST}"
: "${MP_DB_NAME:?Definir MP_DB_NAME}"
: "${MP_DB_USER:?Definir MP_DB_USER}"
: "${MP_DB_PASSWORD:?Definir MP_DB_PASSWORD}"
DUMP="$(command -v mariadb-dump || command -v mysqldump || true)"
[[ -n "$DUMP" ]] || { echo 'Falta mariadb-dump/mysqldump' >&2; exit 2; }
TARGET="${MP_BACKUP_DIR:-$BASE/../mpi_private_backups}"
mkdir -p "$TARGET"
TARGET="$(cd "$TARGET" && pwd)"
[[ "$TARGET/" != "$(cd "$BASE/public" && pwd)/"* ]] || { echo 'No guardar backups dentro de public/' >&2; exit 2; }
TMP="$(mktemp -d)"; trap 'rm -rf "$TMP"' EXIT
mkdir -p "$TMP/uploads"
cp -a "$BASE/public/uploads/media" "$TMP/uploads/"
cp -a "$BASE/public/uploads/products" "$TMP/uploads/"
export MYSQL_PWD="$MP_DB_PASSWORD"
"$DUMP" --host="$MP_DB_HOST" --port="${MP_DB_PORT:-3306}" --user="$MP_DB_USER" --single-transaction --quick --routines --triggers --events --default-character-set=utf8mb4 "$MP_DB_NAME" > "$TMP/site.sql"
unset MYSQL_PWD
[[ -s "$TMP/site.sql" ]] || { echo 'La copia SQL está vacía' >&2; exit 3; }
cat > "$TMP/LEER.txt" <<'EOF'
Respaldo SQL y carpetas uploads de Mega Prime Import.
NO incluye credenciales. Restaurar solo en una base de pruebas, nunca sobre producción sin respaldo y autorización.
Tras restaurar, comprobar datos e imágenes desde la aplicación.
EOF
STAMP="$(date -u +%Y%m%dT%H%M%SZ)"
FILE="$TARGET/mpi_${STAMP}.tar.gz"
tar -czf "$FILE" -C "$TMP" site.sql uploads LEER.txt
tar -tzf "$FILE" >/dev/null
sha256sum "$FILE" > "$FILE.sha256"
echo "Copia generada: $FILE"
echo 'Traslada el respaldo a un destino externo cifrado y ensaya una restauración en una base diferente.'
