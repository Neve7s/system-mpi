#!/usr/bin/env bash
set -euo pipefail
[[ $# -eq 1 ]] || { echo 'Uso: bash verify_backup.sh /ruta/mpi_FECHA.tar.gz' >&2; exit 2; }
FILE="$1"
[[ -f "$FILE" ]] || { echo 'No existe el archivo' >&2; exit 2; }
if [[ -f "$FILE.sha256" ]];then (cd "$(dirname "$FILE")" && sha256sum -c "$(basename "$FILE").sha256");fi
tar -tzf "$FILE" >/dev/null
for name in site.sql uploads/media/ uploads/products/;do
  if ! tar -tzf "$FILE" | grep -q "^${name}";then echo "No se encontró $name" >&2; exit 3;fi
done
[[ "$(tar -xOzf "$FILE" site.sql | wc -c)" -gt 0 ]] || { echo 'SQL vacío' >&2;exit 3; }
echo 'Estructura, compresión y SHA (si existe) correctos. Falta restauración real en base SEPARADA.'
