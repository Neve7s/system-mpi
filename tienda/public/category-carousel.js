/* V80 · carrusel de categorías, navegación, pausa por interacción y opciones CMS */
(()=>{'use strict';
 const rail=document.getElementById('categories'),prev=document.getElementById('mpi77CatPrev'),next=document.getElementById('mpi77CatNext'),pause=document.getElementById('mpi77CatAuto'),status=document.getElementById('mpi77CatStatus');
 if(!rail||!prev||!next||!pause)return;
 const reduced=matchMedia('(prefers-reduced-motion: reduce)');let settings={autoplay:true,seconds:5.6,desktopCards:3};let timer=null,interacting=false;
 function items(){return [...rail.querySelectorAll('.category')]}
 function step(){const first=items()[0];return first?first.getBoundingClientRect().width+(parseFloat(getComputedStyle(rail).columnGap)||14):rail.clientWidth}
 function maxScroll(){return Math.max(0,rail.scrollWidth-rail.clientWidth)}
 function update(){const cards=items(),max=maxScroll(),active=Math.max(0,Math.min(cards.length-1,Math.round(rail.scrollLeft/Math.max(step(),1))));
  const count=Math.max(1,Math.round(rail.clientWidth/Math.max(step(),1)));const end=Math.min(cards.length,active+count);
  prev.disabled=next.disabled=max<3;prev.hidden=next.hidden=max<3;pause.hidden=max<3;
  pause.textContent=settings.autoplay?'Ⅱ Pausar':'▶ Reanudar';pause.setAttribute('aria-pressed',String(!settings.autoplay));
  if(status)status.textContent=cards.length?`Mostrando ${active+1}–${end} de ${cards.length} categorías`:'No hay categorías';
 }
 function move(dir){const max=maxScroll();if(max<3)return;const reducedNow=reduced.matches;const left=rail.scrollLeft;
  if(dir>0 && left>=max-step()*.5)rail.scrollTo({left:0,behavior:reducedNow?'auto':'smooth'});
  else if(dir<0 && left<=step()*.5)rail.scrollTo({left:max,behavior:reducedNow?'auto':'smooth'});
  else rail.scrollBy({left:dir*step(),behavior:reducedNow?'auto':'smooth'});
 }
 function stop(){if(timer!==null)clearInterval(timer);timer=null}
 function start(){stop();update();if(settings.autoplay&&!reduced.matches&&!document.hidden&&maxScroll()>3){timer=setInterval(()=>{if(!interacting&&!rail.matches(':hover')&&!rail.contains(document.activeElement)&&!document.querySelector('.modal.open'))move(1)},settings.seconds*1000)}}
 function setOptions(input={}){settings={autoplay:input.autoplay!==false,seconds:Math.max(3,Math.min(20,Number(input.seconds)||5.6)),desktopCards:Math.max(2,Math.min(4,Math.round(Number(input.desktopCards)||3)))};start()}
 window.MPI_SET_CATEGORY_OPTIONS=setOptions;
 prev.addEventListener('click',()=>{move(-1);start()});next.addEventListener('click',()=>{move(1);start()});pause.addEventListener('click',()=>{settings.autoplay=!settings.autoplay;start()});
 rail.addEventListener('scroll',update,{passive:true});rail.addEventListener('keydown',e=>{if(e.key==='ArrowLeft'||e.key==='ArrowRight'){e.preventDefault();move(e.key==='ArrowLeft'?-1:1)}});
 rail.addEventListener('touchstart',()=>{interacting=true},{passive:true});for(const ev of ['touchend','touchcancel'])rail.addEventListener(ev,()=>{interacting=false;start()},{passive:true});
 rail.addEventListener('focusin',stop);rail.addEventListener('focusout',()=>setTimeout(start,75));
 document.addEventListener('visibilitychange',start);window.addEventListener('resize',start);
 new MutationObserver(()=>{rail.scrollLeft=Math.min(rail.scrollLeft,maxScroll());start()}).observe(rail,{childList:true});
 if(typeof reduced.addEventListener==='function')reduced.addEventListener('change',start);
 setOptions(window.MPI_CATEGORY_OPTIONS||window.MPI_CMS_ACTIVE?.categoryCarousel||window.MPI_CMS_DEFAULTS?.categoryCarousel||{});
})();