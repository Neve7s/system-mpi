#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "${BASH_SOURCE[0]}")/.."
echo '== PHP lint =='
find private public tools -type f -name '*.php' -print0 | xargs -0 -n1 php -l
echo '== Validaciones PHP =='
php tools/pruebas_codigo.php
php tools/pruebas_seguridad_v85_1.php
echo '== Motor de liquidación =='
node tools/pruebas_motor.cjs
echo '== Estructura y responsive (estático) =='
python3 tools/auditoria_estructura_v85_1.py
echo '== AVISO: ejecutar MariaDB y pruebas visuales manualmente en hosting =='
