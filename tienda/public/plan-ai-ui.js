/* Mega Prime Import V93.8 · procesamiento local de planos en el navegador.
   No se envían planos ni se consultan claves a ninguna API de IA del servidor.
   PDF.js y Tesseract.js pueden descargarse como librerías libres desde una CDN:
   el PDF y la imagen se procesan localmente con dichas librerías. */
(()=>{'use strict';
 const $=id=>document.getElementById(id);
 const panel=$('mpiPlanAi');if(!panel)return;
 const read=$('r45AiRead'),manual=$('r45AiManual'),status=$('r45AiStatus'),results=$('r45AiResults'),fileInput=$('r45File'),capabilities=$('r45AiCapabilities');
 const MAX_SIZE=8*1024*1024,MAX_PAGES=3,MAX_PLANES=20;
 const PDF_CDN='https://cdn.jsdelivr.net/npm/pdfjs-dist@4.10.38/build/pdf.mjs';
 const PDF_WORKER='https://cdn.jsdelivr.net/npm/pdfjs-dist@4.10.38/build/pdf.worker.mjs';
 const OCR_SCRIPT='https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/tesseract.min.js';
 const OCR_WORKER='https://cdn.jsdelivr.net/npm/tesseract.js@5.1.1/dist/worker.min.js';
 const OCR_CORE='https://cdn.jsdelivr.net/npm/tesseract.js-core@5.1.0';
 let proposal=null, busy=false, pdfEngine=null, capabilitiesState={status:'unavailable',options:null};
 const esc=s=>String(s??'').replace(/[&<>"']/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
 const finite=v=>typeof v==='number'&&Number.isFinite(v);
 function notify(text,error=false){status.textContent=text;status.classList.toggle('error',!!error);}
 function updateRead(){read.disabled=busy||!fileInput.files||!fileInput.files.length;read.textContent=busy?'Leyendo plano en este dispositivo…':'Leer plano en mi navegador';}
 function cleanNum(x){if(x===null||x===undefined||x==='')return null;const n=Number(String(x).trim().replace(',','.'));return Number.isFinite(n)?n:null;}
 function lengthM(value,unit='m'){const n=cleanNum(value);if(n===null||n<=0)return null;const u=String(unit).toLowerCase();return Math.round(n*(u==='mm'?.001:u==='cm'?.01:1)*10000)/10000;}
 const DEFAULT={name:'Agua 1',shape:'unknown',base_m:null,top_m:null,run_m:null,slope_value:null,slope_unit:'unknown',confidence:'Por verificar',evidence:''};
 const shapeOf=s=>{const q=String(s||'').toLowerCase();return q==='rectangle'||q.includes('rect')?'rectangle':q==='triangle'||q.includes('triang')?'triangle':q==='trapezoid'||q.includes('trap')?'trapezoid':'unknown';};
 const slopeUnitOf=s=>{const q=String(s||'').toLowerCase();return q==='pct'||q==='%'||q.includes('porcen')?'pct':q==='deg'||q==='°'||q.includes('grado')?'deg':'unknown';};
 function normalizePlanes(raw){
   const items=Array.isArray(raw)?raw:Array.isArray(raw?.planes)?raw.planes:[];
   return items.slice(0,MAX_PLANES).map((p,i)=>({
     name:String(p.name||p.nombre||`Agua ${i+1}`).slice(0,70),shape:shapeOf(p.shape||p.forma),
     base_m:cleanNum(p.base_m??p.base),top_m:cleanNum(p.top_m??p.top),run_m:cleanNum(p.run_m??p.run),
     slope_value:cleanNum(p.slope_value??p.slope),slope_unit:slopeUnitOf(p.slope_unit||p.unit),
     confidence:String(p.confidence||'Por verificar').slice(0,45),evidence:String(p.evidence||'').slice(0,350)
   }));
 }
 function parseResponse(text){
   try{const raw=String(text).replace(/```(?:json)?/gi,'').trim();const start=raw.indexOf('{'),end=raw.lastIndexOf('}');if(start<0||end<start)throw Error('sin JSON');
     const j=JSON.parse(raw.slice(start,end+1));return {document_summary:String(j.document_summary||'Plano analizado en este dispositivo.'),unit_note:String(j.unit_note||''),warnings:Array.isArray(j.warnings)?j.warnings.slice(0,12).map(String):[],planes:normalizePlanes(j.planes)};
   }catch{return null;}
 }
 function parseTextCotas(text){
   const t=String(text||'').replace(/\u00a0/g,' ').replace(/[\t\r]+/g,' ').slice(0,40000),
      warnings=['Lectura automática orientativa: confirma cada medida, su dirección, los aleros y la pendiente.'];
   const dimension=(keys)=>{for(const key of keys){const re=new RegExp('(?:\\b'+key+'\\b)\\s*(?:[:=\\-]|de)?\\s*(\\d{1,5}(?:[.,]\\d{1,4})?)\\s*(mm|cm|m|metros?)?','i');const m=t.match(re);if(m)return lengthM(m[1],m[2]&&m[2].startsWith('metro')?'m':m[2]||'m');}return null;};
   let base=dimension(['base','alero','ancho','frente']),run=dimension(['recorrido\\s+horizontal','proyecci[oó]n','fondo','largo','profundidad']);
   const pair=t.match(/(?:dimensiones?\s*:?\s*)?(\d{1,5}(?:[.,]\d{1,4})?)\s*(mm|cm|m)?\s*[×xX]\s*(\d{1,5}(?:[.,]\d{1,4})?)\s*(mm|cm|m)?/);
   if(pair){const u=pair[4]||pair[2]||'m';base??=lengthM(pair[1],pair[2]||u);run??=lengthM(pair[3],u);warnings.push('En la cota ancho × largo, verifica cuál es el alero y cuál el recorrido horizontal.');}
   const top=dimension(['base\\s+superior','borde\\s+superior']);
   let shape=/trapeci/i.test(t)?'trapezoid':/tri[aá]ngu/i.test(t)?'triangle':/rect[aá]ngu/i.test(t)?'rectangle':'unknown';
   if(shape==='unknown'&&pair)shape='rectangle';
   const slope=t.match(/\b(?:pendiente|inclinaci[oó]n)\s*(?::|=|de)?\s*(\d{1,3}(?:[.,]\d{1,3})?)\s*(%|°|grados?)/i);
   const slopeVal=slope?cleanNum(slope[1]):null,slopeUnit=slope?slope[2].startsWith('%')?'pct':'deg':'unknown';
   if(!slope)warnings.push('No se identificó la pendiente con unidad (% o grados); debes introducirla.');
   if(base===null||run===null)warnings.push('No se identificaron todas las cotas de alero y recorrido: completa las casillas vacías.');
   if(shape==='unknown')warnings.push('No se reconoció la geometría; elige rectángulo, triángulo o trapecio.');
   const found=base!==null||run!==null||slope!==null||shape!=='unknown';
   return {document_summary:found?'Cotas sugeridas desde el texto leído localmente.':'No se identificaron cotas suficientes de forma automática.',
     unit_note:'Solo se proponen longitudes en metros y pendientes con su unidad explícita.',warnings,
     planes:found?normalizePlanes([{name:'Agua 1',shape,base_m:base,top_m:top,run_m:run,slope_value:slopeVal,slope_unit:slopeUnit,
       confidence:'Requiere verificación',evidence:t.replace(/\s+/g,' ').slice(0,240)}]):[]};
 }
 async function detectModel(){
   const api=window.LanguageModel;
   if(!api||typeof api.availability!=='function'||typeof api.create!=='function'){
     capabilitiesState={status:'unavailable',options:null};
     capabilities.textContent='IA visual integrada: no disponible en este navegador. Puedes intentar OCR local, lectura de texto PDF o ingresar las medidas manualmente.';
     return capabilitiesState;
   }
   const settings=[{expectedInputs:[{type:'text',languages:['es']},{type:'image'}],expectedOutputs:[{type:'text',languages:['es']}]},
                   {expectedInputs:[{type:'text',languages:['en']},{type:'image'}],expectedOutputs:[{type:'text',languages:['en']}]}];
   for(const options of settings){
     try{const state=await api.availability(options);
       if(['available','downloadable','downloading'].includes(state)){
         capabilitiesState={status:state,options};
         capabilities.textContent=state==='available'?'IA visual integrada: disponible, reconocimiento local de imágenes.':
          'IA visual integrada: el navegador necesita descargar/preparar su modelo gratuito. Inicia la lectura con un clic; puede consumir espacio de almacenamiento.';
         return capabilitiesState;
       }
     }catch{/* Probar una configuración de idioma más compatible */}
   }
   capabilitiesState={status:'unavailable',options:null};
   capabilities.textContent='IA visual no compatible con este equipo. Se usará OCR local o texto de PDF, sin IA externa.';
   return capabilitiesState;
 }
 async function loadPdf(){
   if(pdfEngine)return pdfEngine;
   if(window.pdfjsLib?.getDocument){pdfEngine=window.pdfjsLib;return pdfEngine;}
   const module=await import(PDF_CDN);
   module.GlobalWorkerOptions.workerSrc=PDF_WORKER;
   pdfEngine=module;return module;
 }
 async function inspectPdf(file){
   const pdfjs=await loadPdf(),bytes=new Uint8Array(await file.arrayBuffer());
   const pdf=await pdfjs.getDocument({data:bytes}).promise;
   const texts=[],visuals=[];let warning='';
   try{
     const count=Math.min(pdf.numPages,MAX_PAGES);
     if(pdf.numPages>MAX_PAGES)warning=`Se examinaron ${count} páginas de ${pdf.numPages}; las demás deben comprobarse aparte.`;
     for(let pageNo=1;pageNo<=count;pageNo++){
       const page=await pdf.getPage(pageNo),tc=await page.getTextContent();
       texts.push(tc.items.map(i=>i.str||'').join(' '));
       const viewport=page.getViewport({scale:1});
       const scale=Math.min(1.5,1700/Math.max(viewport.width,viewport.height));
       const vp=page.getViewport({scale});const canvas=document.createElement('canvas');
       canvas.width=Math.ceil(vp.width);canvas.height=Math.ceil(vp.height);
       await page.render({canvasContext:canvas.getContext('2d',{alpha:false}),viewport:vp}).promise;
       visuals.push(canvas);
     }
   }finally{await pdf.destroy();}
   return {text:texts.join(' \n '),visuals,warning};
 }
 async function loadTesseract(){
   if(window.Tesseract?.createWorker)return window.Tesseract;
   await new Promise((resolve,reject)=>{
     const script=document.createElement('script');script.src=OCR_SCRIPT;script.crossOrigin='anonymous';script.onload=resolve;script.onerror=()=>reject(Error('No fue posible descargar el motor OCR gratuito. Comprueba la conexión o introduce las cotas manualmente.'));document.head.append(script);
   });
   if(!window.Tesseract?.createWorker)throw Error('El motor OCR no se inicializó correctamente.');return window.Tesseract;
 }
 async function recognizeOCR(visuals){
   const T=await loadTesseract();notify('Reconociendo texto con OCR en este dispositivo…');
   const worker=await T.createWorker('spa',1,{workerPath:OCR_WORKER,corePath:OCR_CORE,langPath:'https://tessdata.projectnaptha.com/4.0.0'});
   try{const parts=[];for(const canvas of visuals){const r=await worker.recognize(canvas);parts.push(String(r.data?.text||''));}return parts.join('\n');}
   finally{await worker.terminate();}
 }
 async function readVisualAI(visuals){
   if(capabilitiesState.status==='unavailable'||!capabilitiesState.options)return null;
   const api=window.LanguageModel;let session;
   try{
     notify('Interpretando el plano con IA integrada en este navegador. Si es el primer uso, el modelo gratuito puede descargarse…');
     session=await api.create(capabilitiesState.options);
     const pages=visuals.slice(0,MAX_PAGES);
     const items=[{type:'text',value:'Eres un lector cuidadoso de planos de cubierta. Devuelve UNICAMENTE JSON válido en el siguiente formato: {"document_summary":"...","unit_note":"...","warnings":["..."],"planes":[{"name":"Agua 1","shape":"rectangle|triangle|trapezoid|unknown","base_m":null,"top_m":null,"run_m":null,"slope_value":null,"slope_unit":"pct|deg|unknown","confidence":"baja|media|alta","evidence":"cota escrita y ubicación"}]}. Extrae solo cotas ESCRITAS y visibles; convierte cm/mm a metros. No inventes longitud, pendiente, cantidad de faldones ni forma. Si la orientación alero/recorrido es dudosa, usa null y adviértelo. Si falta la pendiente, deja slope_value:null, slope_unit:unknown. Máximo 20 faldones; no confundas dimensiones dibujadas con medidas reales. La revisión humana es obligatoria.'}];
     for(const v of pages)items.push({type:'image',value:v});
     const answer=await session.prompt([{role:'user',content:items}]);return parseResponse(answer);
   }finally{try{session?.destroy?.();}catch{/* No bloquear extracción local */}}
 }
 function selectInput(name,label,values,value){return `<label>${label}<select data-ai="${name}">${values.map(([v,t])=>`<option value="${v}"${v===value?' selected':''}>${t}</option>`).join('')}</select></label>`;}
 function inputNumber(name,label,value,min='0'){return `<label>${label}<input data-ai="${name}" type="number" step="any" min="${min}" value="${finite(value)?value:''}" placeholder="Por determinar"></label>`;}
 function appendRow(p,i){
    const row=document.createElement('section');row.className='mpi-ai-row';row.dataset.index=String(i);
    row.innerHTML=`<div class="mpi-ai-row-head"><b>Faldón ${i+1}</b><span class="mpi-ai-pill">${esc(p.confidence||'Manual')}</span><button type="button" class="mpi-ai-remove" aria-label="Eliminar faldón ${i+1}">Eliminar</button></div>
      <div class="mpi-ai-grid">
       <label>Nombre<input data-ai="name" maxlength="70" value="${esc(p.name||`Agua ${i+1}`)}"></label>
       ${selectInput('shape','Forma',[['unknown','Por determinar'],['rectangle','Rectángulo'],['triangle','Triángulo'],['trapezoid','Trapecio']],p.shape)}
       ${inputNumber('base','Base / alero (m)',p.base_m,'0.01')}
       ${inputNumber('top','Base superior (m, trapecio)',p.top_m,'0.01')}
       ${inputNumber('run','Recorrido horizontal (m)',p.run_m,'0.01')}
       ${inputNumber('slope','Pendiente',p.slope_value)}
       ${selectInput('unit','Unidad pendiente',[['unknown','Por determinar'],['pct','Porcentaje (%)'],['deg','Grados (°)']],p.slope_unit)}
      </div>
      <p class="mpi-ai-evidence">Evidencia: ${esc(p.evidence||'Ingreso manual; comprobar contra el plano original.')}</p>
      <label class="mpi-ai-consent mpi-ai-review"><input data-ai="verified" type="checkbox"><span>Verifiqué la geometría, cotas y pendiente de este faldón contra el plano/obra.</span></label>`;
    row.querySelector('.mpi-ai-remove').addEventListener('click',()=>{row.remove();renumber();});
    row.querySelectorAll('input:not([type="checkbox"]),select').forEach(el=>el.addEventListener('input',()=>{row.querySelector('[data-ai="verified"]').checked=false;}));
    results.append(row);
 }
 function renumber(){results.querySelectorAll('.mpi-ai-row').forEach((row,i)=>{row.dataset.index=String(i);row.querySelector('b').textContent=`Faldón ${i+1}`;});}
 function render(data){proposal=data;results.replaceChildren();
   const summary=document.createElement('p');summary.textContent=data.document_summary||'Plano leído.';results.append(summary);
   if(data.unit_note){const note=document.createElement('p');note.textContent=data.unit_note;results.append(note);}
   if(data.warnings?.length){const ul=document.createElement('ul');ul.className='mpi-ai-warnings';for(const w of data.warnings){const li=document.createElement('li');li.textContent=String(w);ul.append(li);}results.append(ul);}
   const heading=document.createElement('h5');heading.textContent='Revisión obligatoria: corrige las cotas antes de calcular';results.append(heading);
   const toolbar=document.createElement('div');toolbar.className='mpi-ai-controls';const add=document.createElement('button');add.type='button';add.className='mpi-ai-secondary';add.textContent='Añadir otro faldón';add.onclick=()=>addManual();toolbar.append(add);
   const apply=document.createElement('button');apply.type='button';apply.id='r45AiApply';apply.textContent='Aplicar faldones verificados';apply.onclick=applyReviewed;toolbar.append(apply);results.append(toolbar);
   const rows=normalizePlanes(data.planes);if(!rows.length)rows.push({...DEFAULT});rows.forEach(appendRow);
 }
 function addManual(){
   if(!proposal){render({document_summary:'Ingreso manual de dimensiones, sin IA externa.',unit_note:'',warnings:['Introduce la forma, alero, recorrido horizontal y pendiente; comprueba todas las medidas.'],planes:[]});notify('Introduce y confirma las medidas del faldón para continuar.');return;}
   const count=results.querySelectorAll('.mpi-ai-row').length;
   if(count>=MAX_PLANES){notify(`Se permite un máximo de ${MAX_PLANES} faldones por lectura.`,true);return;}
   appendRow({...DEFAULT,name:`Agua ${count+1}`},count);
 }
 function applyReviewed(){
   const rows=[...results.querySelectorAll('.mpi-ai-row')];const planes=[];
   if(!rows.length||rows.length>MAX_PLANES){notify('Debe existir entre 1 y 20 faldones para aplicar.',true);return;}
   for(const [i,row] of rows.entries()){
     const get=k=>row.querySelector('[data-ai="'+k+'"]');const n=k=>{const v=get(k).value.trim();return v===''?NaN:Number(v);};
     const shape=get('shape').value,unit=get('unit').value,base=n('base'),run=n('run'),top=n('top'),slope=n('slope');
     if(!get('verified').checked){notify(`Debes confirmar el faldón ${i+1} antes de aplicarlo.`,true);return;}
     if(!['rectangle','triangle','trapezoid'].includes(shape)||!['pct','deg'].includes(unit)||
       !Number.isFinite(base)||base<=0||base>10000||!Number.isFinite(run)||run<=0||run>10000||
       !Number.isFinite(slope)||slope<0||(unit==='pct'&&slope>500)||(unit==='deg'&&slope>=90)||
       (shape==='trapezoid'&&(!Number.isFinite(top)||top<=0||top>10000))){
       notify(`Faldón ${i+1}: completa la forma, cotas y pendiente con su unidad antes de continuar. No se aplicó ningún cambio.`,true);return;}
     planes.push({name:get('name').value.trim().slice(0,70)||`Agua ${i+1}`,shape,base,top:shape==='trapezoid'?top:'',run,slope,unit});
   }
   if(!window.MPI45||typeof window.MPI45.applyAIPlanes!=='function'){notify('No está disponible la calculadora de materiales en esta página.',true);return;}
   if(window.MPI45.applyAIPlanes(planes))notify(`${planes.length} faldón(es) verificados incorporados a la calculadora. Revisa los accesorios y pulsa CALCULAR.`);
 }
 async function analyze(){
   if(busy)return;const file=fileInput.files?.[0];if(!file){notify('Selecciona primero un plano JPG, PNG, WEBP o PDF.',true);return;}
   const name=file.name.toLowerCase();const pdf=name.endsWith('.pdf')||file.type==='application/pdf',image=/\.(png|jpe?g|webp)$/.test(name)||/^image\/(png|jpeg|webp)$/.test(file.type);
   if(!pdf&&!image){notify('Formato no compatible. Convierte el plano a PDF, JPG, PNG o WEBP.',true);return;}
   if(file.size<=0||file.size>MAX_SIZE){notify('Selecciona un archivo no vacío de hasta 8 MB.',true);return;}
   busy=true;updateRead();results.replaceChildren();proposal=null;
   let text='',visuals=[],warning='';const notes=[];
   try{
     if(pdf){notify('Abriendo PDF en el navegador y recuperando sus cotas…');const doc=await inspectPdf(file);text=doc.text;visuals=doc.visuals;warning=doc.warning;}
     else{visuals=[file];}
     let result=null;
     if(capabilitiesState.status!=='unavailable'){
       try{result=await readVisualAI(visuals);}catch(e){notes.push('La IA integrada no pudo analizar esta imagen: '+String(e.message||e).slice(0,120)+'. Se intentará lectura local de texto.');}
     }
     if(!result||!result.planes?.length){
       // Un PDF puede contener un título como texto, con todas las cotas dibujadas.
       // En ese caso también hacemos OCR de la imagen, aunque el PDF tenga texto extraíble.
       const pre=parseTextCotas(text);
       const cota=pre.planes?.[0];
       const missingCotas=!cota||cota.base_m===null||cota.run_m===null||cota.slope_value===null;
       if(missingCotas && visuals.length){
         try{
           const ocrText=await recognizeOCR(visuals);
           if(ocrText.trim())text=(text.trim()?text+'\n':'')+ocrText;
         }catch(e){notes.push(String(e.message||e));}
       }
       result=parseTextCotas(text);
     }
     if(warning)notes.push(warning);
     if(notes.length)result.warnings=[...(result.warnings||[]),...notes];
     if(!result.planes?.length)result.warnings.push('No se detectaron cotas suficientes. Completa el faldón manualmente; NO se inventarán medidas.');
     render(result);
     notify(result.planes?.length?'Lectura local terminada. Verifica cada cota antes de incorporar los faldones.':'No se obtuvieron cotas confiables. Completa las medidas manualmente.');
   }catch(e){render({document_summary:'No fue posible extraer automáticamente las cotas.',unit_note:'',warnings:[String(e.message||e).slice(0,250),'Puedes introducir los faldones manualmente. No se envió ningún plano a un servidor de IA.'],planes:[]});notify('No fue posible leer el archivo automáticamente. Usa la revisión manual.',true);}
   finally{busy=false;updateRead();}
 }
 read.addEventListener('click',analyze);
 manual.addEventListener('click',addManual);
 fileInput.addEventListener('change',()=>{proposal=null;results.replaceChildren();updateRead();});
 window.MPI_BrowserPlan={parseTextCotas,normalizePlanes,parseResponse,detectModel};
 updateRead();detectModel().catch(()=>{capabilities.textContent='No fue posible consultar la IA integrada. OCR y revisión manual disponibles.';});
})();
