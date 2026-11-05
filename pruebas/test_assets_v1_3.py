#!/usr/bin/env python3
"""Visual check with network resources fulfilled from local files, not from actual domain."""
from pathlib import Path
from playwright.sync_api import sync_playwright
from urllib.parse import urlparse, unquote
import json,mimetypes
ROOT=Path(__file__).resolve().parents[1]
CASES=[('tienda',ROOT/'tienda/public','index.html',['.mpi-brand-emblem','.mpi-footer-emblem','.mpi-hero-kicker img']),('catalogo',ROOT/'tienda/public','catalogo-digital.html',['.topbar .brand img'])]
results=[]
with sync_playwright() as pw:
 browser=pw.chromium.launch(headless=True,executable_path='/usr/bin/chromium',args=['--no-sandbox','--disable-dev-shm-usage'])
 for name,folder,index,selectors in CASES:
  for width in [390,1280]:
   pg=browser.new_page(viewport={'width':width,'height':850})
   def resources(route):
    url=urlparse(route.request.url)
    rel=unquote(url.path).lstrip('/')
    target=(folder/rel).resolve()
    if url.hostname=='mpi-test.invalid' and target.is_file() and target.is_relative_to(folder.resolve()):
     typ=mimetypes.guess_type(str(target))[0] or 'application/octet-stream'
     route.fulfill(status=200,body=target.read_bytes(),content_type=typ)
    else: route.abort()
   pg.route('**/*',resources)
   markup=(folder/index).read_text(encoding='utf-8').replace('</head>','<base href="https://mpi-test.invalid/"></head>',1)
   pg.set_content(markup,wait_until='domcontentloaded',timeout=45000)
   pg.wait_for_timeout(1000)
   js="""selectors=>({images:selectors.map(s=>{let e=document.querySelector(s);return {selector:s,exists:!!e,src:e?.getAttribute('src')||'',complete:!!e?.complete,naturalWidth:e?.naturalWidth||0,display:e?getComputedStyle(e).display:null}}),overflow:document.documentElement.scrollWidth-innerWidth})"""
   info=pg.evaluate(js,selectors)
   ok=all(x['exists'] and x['naturalWidth']>0 for x in info['images']) and info['overflow']<=1
   results.append({'page':name,'width':width,'pass':ok,**info})
   print(('PASS' if ok else 'FAIL'),name,width,[(i['selector'],i['naturalWidth'],i['display']) for i in info['images']],'overflow',info['overflow'],flush=True)
   pg.screenshot(path=str(ROOT/f'pruebas/resultados_v1_3/visual_{name}_{width}.png'),full_page=False)
   pg.close()
 browser.close()
report={'name':'Recursos y responsive V1.3 con recursos simulados','cases':results,'passed':sum(i['pass'] for i in results),'failed':sum(not i['pass'] for i in results),'limit':'Assets fulfilled by local files; does not prove real HTTPS/domain hosting.'}
(ROOT/'pruebas/resultados_v1_3/assets_real.json').write_text(json.dumps(report,ensure_ascii=False,indent=2))
assert report['failed']==0,report
