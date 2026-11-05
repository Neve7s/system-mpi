(()=>{'use strict';
 const catalog=JSON.parse(document.getElementById('mpiCatalogData')?.textContent||'[]');
 const lookup=new Map(catalog.map(p=>[p.id,p]));const $=s=>document.querySelector(s), $$=s=>[...document.querySelectorAll(s)];
 const currency=n=>new Intl.NumberFormat('es-EC',{style:'currency',currency:'USD'}).format(n);
 let cart=[];try{const stored=JSON.parse(localStorage.getItem('mpi_store_cart_v83')||'[]');if(Array.isArray(stored))cart=stored.filter(x=>{const p=lookup.get(x.id);return !!p&&Number.isInteger(x.qty)&&x.qty>0&&x.qty<=10000&&Number.isInteger(x.variant)&&x.variant>=0&&(p.variants.length?x.variant<p.variants.length:x.variant===0);}).slice(0,30);}catch{}
 function persist(){try{localStorage.setItem('mpi_store_cart_v83',JSON.stringify(cart));}catch{}$('#cartCount').textContent=String(cart.reduce((s,l)=>s+l.qty,0));}
 const variantFor=(p,index)=>p.variants.length?p.variants[index]:{label:'Estándar',price:p.price};
 let activeCat='all';
 function filter(){let shown=0;const term=$('#searchProducts').value.trim().toLocaleLowerCase('es');$$('.product-card').forEach(el=>{const ok=(activeCat==='all'||el.dataset.category===activeCat)&&el.dataset.search.toLocaleLowerCase('es').includes(term);el.hidden=!ok;if(ok)shown++;});$('#noProducts').hidden=shown!==0;}
 $$('.category-tile').forEach(btn=>btn.addEventListener('click',()=>{activeCat=btn.dataset.category;$$('.category-tile').forEach(b=>{b.classList.toggle('selected',b===btn);b.setAttribute('aria-pressed',String(b===btn));});filter();$('#catalogo').scrollIntoView({behavior:'smooth',block:'start'});}));
 $$('.category-tile').forEach(b=>b.setAttribute('aria-pressed',String(b.classList.contains('selected'))));
 $('#searchProducts').addEventListener('input',filter);
 $('#catPrev').addEventListener('click',()=>$('#categoryCarousel').scrollBy({left:-340,behavior:'smooth'}));$('#catNext').addEventListener('click',()=>$('#categoryCarousel').scrollBy({left:340,behavior:'smooth'}));
 function openVariants(id){const p=lookup.get(id);if(!p)return;const container=$('#variantBody');container.replaceChildren();
  const name=document.createElement('h2');name.textContent=p.name;container.append(name);
  const form=document.createElement('form');form.className='variant-form';const label=document.createElement('label');label.textContent='Selecciona tu presentación';const select=document.createElement('select');
  (p.variants.length?p.variants:[{label:'Presentación estándar',price:p.price}]).forEach((v,i)=>{const option=document.createElement('option');option.value=String(i);option.textContent=v.label+' · '+(Number(v.price)>0?currency(v.price):'Precio a confirmar');select.append(option);});
  label.append(select);form.append(label);const quantity=document.createElement('label');quantity.textContent='Cantidad';const q=document.createElement('input');q.type='number';q.min='1';q.max='10000';q.step='1';q.value='1';q.required=true;quantity.append(q);form.append(quantity);
  const btn=document.createElement('button');btn.type='submit';btn.className='btn gold';btn.textContent='Agregar al carrito';form.append(btn);
  form.addEventListener('submit',e=>{e.preventDefault();const qty=Number(q.value),variant=Number(select.value);if(!Number.isInteger(qty)||qty<1||qty>10000)return;if(!variantFor(p,variant))return;
   const old=cart.find(x=>x.id===id&&x.variant===variant);if(old){if(old.qty+qty>10000){alert('Cantidad máxima 10000');return;}old.qty+=qty;}
   else{if(cart.length>=30){alert('Máximo 30 presentaciones');return;}cart.push({id,qty,variant});}persist();$('#variantDialog').close();renderCart();$('#cartDialog').showModal();});container.append(form);$('#variantDialog').showModal();}
 $$('[data-add]').forEach(btn=>btn.addEventListener('click',()=>openVariants(Number(btn.dataset.add))));
 function renderCart(){const el=$('#cartItems');el.replaceChildren();let total=0;
  for(const [index,line]of cart.entries()){const p=lookup.get(line.id);if(!p)continue;const v=variantFor(p,line.variant);if(!v)continue;total+=v.price*line.qty;
   const row=document.createElement('div');row.className='cart-row';const info=document.createElement('div');const bold=document.createElement('strong');bold.textContent=p.name;const sub=document.createElement('small');sub.textContent=v.label+' · '+line.qty+' × '+(v.price>0?currency(v.price):'A confirmar');info.append(bold,sub);
   const button=document.createElement('button');button.type='button';button.textContent='Quitar';button.addEventListener('click',()=>{cart.splice(index,1);persist();renderCart();});row.append(info,button);el.append(row);}
  if(!cart.length){const p=document.createElement('p');p.className='muted';p.textContent='El carrito está vacío. Agrega productos antes de solicitar una proforma.';el.append(p);}
  $('#cartTotal').textContent=cart.length?currency(total):'Sin productos';$('#quoteSubmit').disabled=!cart.length;
 }
 $('#openCart').addEventListener('click',()=>{renderCart();$('#cartDialog').showModal();});
 $('#quoteForm').addEventListener('submit',async e=>{e.preventDefault();if(!cart.length)return;const f=e.currentTarget,btn=$('#quoteSubmit'),status=$('#quoteStatus');btn.disabled=true;status.textContent='Registrando solicitud...';
  const fd=new FormData(f),body={customer_name:fd.get('customer_name'),customer_phone:fd.get('customer_phone'),customer_email:fd.get('customer_email'),customer_note:fd.get('customer_note'),website:fd.get('website'),consent:fd.get('consent')==='on',items:cart};
  try{const r=await fetch('quote.php',{method:'POST',credentials:'same-origin',cache:'no-store',headers:{'Content-Type':'application/json','X-CSRF-Token':window.MPI_SITE.csrf},body:JSON.stringify(body)});const out=await r.json();if(!r.ok)throw Error(out.error||'No se pudo registrar');
   status.replaceChildren(document.createTextNode('¡Solicitud registrada! Código: '+out.code+'. '+(out.has_unpriced?'Hay precios pendientes de confirmar.':'Subtotal referencial: $'+out.total+'.')+' Un asesor confirmará los valores. '));
   if(out.whatsapp_url){const a=document.createElement('a');a.href=out.whatsapp_url;a.target='_blank';a.rel='noopener noreferrer';a.textContent='Continuar en WhatsApp';status.append(a);}
   cart=[];persist();renderCart();f.reset();}catch(err){status.textContent=err.message;}finally{btn.disabled=!cart.length;}
 });persist();filter();
})();
