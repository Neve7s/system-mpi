/* V70 · navegación táctil e indicadores de tablas; preserva motores y carrito existentes */
(()=>{'use strict';
 const actions=document.getElementById('mpi70MobileActions');
 if(!actions)return;
 const go=(el)=>el?.scrollIntoView({behavior:matchMedia('(prefers-reduced-motion: reduce)').matches?'auto':'smooth',block:'start'});
 actions.addEventListener('click',event=>{
  const button=event.target.closest('button[data-mpi70]');if(!button)return;
  const action=button.dataset.mpi70;
  if(action==='products')go(document.getElementById('catalogo'));
  else if(action==='calc'&&typeof window.openCalculator==='function')window.openCalculator();
  else if(action==='quote'&&typeof window.openCart==='function')window.openCart();
  else if(action==='book'&&typeof window.openDigitalCatalog==='function')window.openDigitalCatalog();
 });
 const modals=['cartModal','calcModal','detailModal','mpiDigitalViewer'].map(id=>document.getElementById(id)).filter(Boolean);
 const check=()=>document.body.classList.toggle('mpi70-overlay-open',modals.some(el=>el.classList.contains('open')));
 const observer=new MutationObserver(check);
 modals.forEach(el=>observer.observe(el,{attributes:true,attributeFilter:['class']}));
 check();
 const scrollers=document.querySelectorAll('#calcModal .mpi42-scroller,#calcModal .mpi45-table-scroll,#calcModal .mpi49-table-scroll');
 scrollers.forEach(element=>{
  if(element.previousElementSibling?.classList.contains('mpi70-scroll-hint'))return;
  const notice=document.createElement('p');notice.className='mpi70-scroll-hint';notice.textContent='↔ Desliza la tabla hacia los lados para ver todas las columnas.';
  element.parentNode.insertBefore(notice,element);
 });
})();
