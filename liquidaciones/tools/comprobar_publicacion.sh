#!/usr/bin/env bash
# Diagnóstico: no instala ni publica. Requiere una instalación configurada y HTTPS.
set -uo pipefail
ROOT="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT" || exit 1
FAILS=0
run_check(){ printf '\n%s\n' "$1"; shift; "$@" || FAILS=$((FAILS+1)); }
if ! command -v php >/dev/null 2>&1; then echo 'Falta PHP CLI 8.2+.'; exit 1; fi
while IFS= read -r -d '' file; do php -l "$file" || FAILS=$((FAILS+1)); done < <(find public private tools -name '*.php' -type f -print0)
run_check 'Validaciones PHP' php tools/pruebas_codigo.php
run_check 'Entorno de producción' php tools/verificar_entorno.php
run_check 'Conexión y transacciones MariaDB' php tools/prueba_base_datos.php
if command -v node >/dev/null 2>&1; then run_check 'Motor y exportaciones' node tools/pruebas_motor.cjs; else echo 'PENDIENTE: Node.js para repetir las pruebas del motor (puede ejecutarse fuera del hosting).'; FAILS=$((FAILS+1)); fi
if [[ -n "${1:-}" ]]; then run_check 'HTTPS y rutas protegidas' bash tools/probar_http.sh "$1"; else echo 'PENDIENTE: indica https://tu-dominio.com para las pruebas HTTP.'; FAILS=$((FAILS+1)); fi
if [[ $FAILS -eq 0 ]]; then echo 'Comprobaciones automáticas aprobadas. Completar las pruebas manuales del informe antes de publicar.'; else echo "PUBLICACIÓN PENDIENTE: $FAILS comprobaciones fallidas o no ejecutadas."; fi
exit "$([[ $FAILS -eq 0 ]] && echo 0 || echo 1)"
