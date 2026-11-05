#!/usr/bin/env bash
# Sólo comprueba accesibilidad y protección HTTP del sitio ya instalado.
set -uo pipefail
BASE="${1:-}"
if [[ ! "$BASE" =~ ^https://[^/]+/?$ ]]; then echo "Uso: bash tools/probar_http.sh https://tu-dominio.com"; exit 2; fi
BASE="${BASE%/}"; FAILS=0; TOTAL=0
check() {
 local path="$1" allow="$2" label="$3" method="${4:-GET}" code
 if [[ "$method" == "POST" ]]; then code=$(curl -sS -m 15 -o /dev/null -w '%{http_code}' -X POST -H 'Content-Type: application/json' --data '{}' "$BASE$path") || code=000
 else code=$(curl -sS -m 15 -o /dev/null -w '%{http_code}' "$BASE$path") || code=000; fi
 ((TOTAL+=1))
 if [[ " $allow " == *" $code "* ]]; then echo "OK    $label [$code]";else echo "FALLA $label [$code] (se esperó: $allow)";((FAILS+=1));fi
}
check '/' '200' 'Portada pública'
check '/admin.php' '200' 'Formulario de ingreso'
check '/privacidad.php' '200' 'Aviso de privacidad'
check '/panel.php' '302 303' 'Panel requiere autenticación'
check '/copias.php' '401 403' 'Copias privadas inaccesibles'
check '/quote.php' '405' 'API proformas solo POST'
check '/quote.php' '403' 'API rechaza POST sin CSRF' POST
check '/private/config.php' '403 404' 'Config privada no publicada'
check '/tools/instalar.php' '403 404' 'Instalador oculto'
check '/.htaccess' '403 404' 'Archivos ocultos protegidos'
check '/uploads/peligro.php' '403 404' 'Carga de PHP prohibida'
printf '\nRESULTADO: %s/%s\n' "$((TOTAL-FAILS))" "$TOTAL"
exit $([[ $FAILS -eq 0 ]] && echo 0 || echo 1)
