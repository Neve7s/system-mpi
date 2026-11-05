#!/usr/bin/env python3
"""Comprobaciones estáticas de accesibilidad, responsive y estructura. Sin dependencias."""
from html.parser import HTMLParser
from pathlib import Path
import json, re, sys
BASE=Path(__file__).resolve().parents[1]
class Tags(HTMLParser):
    def __init__(self): super().__init__(); self.ids=[]; self.fors=[]; self.viewport=False
    def handle_starttag(self,tag,attrs):
        a=dict(attrs)
        if 'id' in a:self.ids.append(a['id'])
        if tag=='label' and 'for' in a:self.fors.append(a['for'])
        if tag=='meta' and a.get('name')=='viewport':self.viewport=True
results=[]
def check(name,ok,detail=''):
    results.append({'nombre':name,'correcto':bool(ok),'detalle':detail})
for rel in ['private/calculadora.php','vista-local/Calculadora_2026_V85.html','public/index.php']:
    t=(BASE/rel).read_text(encoding='utf-8')
    parser=Tags();parser.feed(t)
    check(f'{rel}: sin IDs duplicados',len(set(parser.ids))==len(parser.ids),f'{len(parser.ids)} identificadores')
    check(f'{rel}: etiquetas con destino',not (set(parser.fors)-set(parser.ids)),f'{len(parser.fors)} etiquetas label[for]')
    check(f'{rel}: viewport adaptable',parser.viewport)
    check(f'{rel}: puntos de corte responsivos','@media' in t if rel!='public/index.php' else (BASE/'public/assets/store.css').read_text().count('@media')>=3)
for asset in ['public/assets/store.css','public/assets/store.js','public/assets/chart.umd.min.js','public/assets/jspdf.umd.min.js','public/assets/xlsx.full.min.js','public/assets/mpi-logo.png']:
    check(f'Recurso presente: {asset}',(BASE/asset).is_file())
report={'correctas':sum(x['correcto'] for x in results),'fallidas':sum(not x['correcto'] for x in results),'pruebas':results}
print(json.dumps(report,ensure_ascii=False,indent=2))
sys.exit(1 if report['fallidas'] else 0)
