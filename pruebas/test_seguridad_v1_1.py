#!/usr/bin/env python3
"""Audita configuración e interfaces de seguridad sin base de datos y sin tocar el servidor."""
from pathlib import Path
import subprocess, tempfile, json, re
R=Path(__file__).resolve().parents[1]
count=0

def check(label,ok):
 global count
 if not ok:raise AssertionError(label)
 count+=1
 print('PASS',label,flush=True)

boot=(R/'acceso/private/bootstrap.php').read_text(encoding='utf-8')
grants=(R/'acceso/public/permissions.php').read_text(encoding='utf-8')
check('HTTPS obligatorio en configuración del centro',"($cfg['require_https']??null)!==true" in boot)
check('Callbacks de ambos clientes validados por configuración',"foreach(['tienda','liquidaciones']" in boot)
check('Protección del origen en formularios autenticados',"HTTP_ORIGIN" in boot and 'sameScheme' in boot and 'sameHost' in boot)
check('CSP y cabeceras de seguridad del centro', 'Content-Security-Policy:' in boot and 'X-Frame-Options: DENY' in boot)
check('Limite por sesión de reintentos de contraseña para permisos',"grant_password_failures" in grants and 'Retry-After' in grants)
check('No otorgar permiso a usuarios desactivados',"No se pueden conceder permisos a cuentas desactivadas" in grants)
check('SSO comprueba permisos servidor',"id_grant" in (R/'acceso/public/status.php').read_text())
check('Se conservan sesiones de aplicación independientes',all('session_name' in (R/p).read_text() for p in ['tienda/app/bootstrap.php','liquidaciones/private/bootstrap.php','acceso/private/bootstrap.php']))
check('No existen secretos reales privados dentro del paquete',not any((R/p).is_file() for p in ['tienda/app/config.local.php','tienda/app/sso.config.php','liquidaciones/private/config.php','liquidaciones/private/sso.config.php','acceso/private/config.php']))
check('Lector local sin API PHP de pago',not (R/'tienda/public/api/plan_ai.php').exists())
demo=json.loads((R/'tienda/fixtures/demo_catalog.json').read_text())
check('Catalogo con 27 productos DEMO',len(demo['products'])==27)
check('Catalogo con 8 categorias DEMO',len(demo['categories'])==8)
check('Modo demostrativo activo',demo.get('demoMode') is True)
media=R/'tienda/public/uploads/products'
imgs=[p for p in media.iterdir() if p.suffix.lower() in ['.png','.jpg','.jpeg','.webp']]
check('36 imagenes disponibles localmente',len(imgs)>=36)
# Verificación dinámica id_cfg en un directorio temporal. No se invoca MariaDB.
with tempfile.TemporaryDirectory(prefix='mpi_v11_cfg_') as tmp:
 tmp=Path(tmp)
 (tmp/'bootstrap.php').write_text(boot)
 (tmp/'runner.php').write_text('<?php require __DIR__."/bootstrap.php"; id_cfg(); echo "CONFIG_OK";')
 base={"base_url":"https://acceso.megaprimeimport.com", "require_https":True,
       "store_db":{"name":"mpi_shop"},"identity_db":{"name":"mpi_identity"},
       "clients":{"tienda":{"redirect_uri":"https://megaprimeimport.com/admin/sso_callback.php", "secret":"a"*64},
                  "liquidaciones":{"redirect_uri":"https://liquidaciones.megaprimeimport.com/sso_callback.php", "secret":"b"*64}}}
 def run(cfg):
  txt='<?php return '+php_export(cfg)+';'
  (tmp/'config.php').write_text(txt)
  proc=subprocess.run(['php',str(tmp/'runner.php')],capture_output=True,text=True,timeout=15)
  return proc.stdout+proc.stderr
 def php_export(obj):
  if isinstance(obj,dict):return '['+','.join(php_export(k)+'=>'+php_export(v) for k,v in obj.items())+']'
  if isinstance(obj,bool):return 'true' if obj else 'false'
  if isinstance(obj,str):return "'"+obj.replace('\\','\\\\').replace("'","\\'")+"'"
  return str(obj)
 check('Configuración HTTPS bien formada aceptada', 'CONFIG_OK' in run(base))
 c=json.loads(json.dumps(base));c['require_https']=False
 check('Configuración insegura HTTP deshabilitado rechazada', 'CONFIG_OK' not in run(c))
 c=json.loads(json.dumps(base));c['clients']['tienda']['secret']='REEMPLAZAR_CON_SECRETO'
 check('Secreto de ejemplo no permite activar SSO','CONFIG_OK' not in run(c))
 c=json.loads(json.dumps(base));c['clients']['liquidaciones']['redirect_uri']='http://liquidaciones.megaprimeimport.com/sso_callback.php'
 check('Callback HTTP rechazado','CONFIG_OK' not in run(c))
print(f'CONTROLES SEGURIDAD V1.1: {count} aprobados, 0 fallidos')
