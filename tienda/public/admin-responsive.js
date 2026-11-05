/* V70: móvil sin alterar el editor de datos ni los permisos PHP existentes. */
(()=>{'use strict';
 const sidebar=document.getElementById('sidebar');
 const layout=document.getElementById('editorLayout');
 const previewToggle=document.getElementById('previewToggle');
 const navToggle=document.getElementById('menuToggle');
 if(!sidebar||!layout||!previewToggle||!navToggle)return;
 const small=()=>matchMedia('(max-width:790px)').matches;
 const sidebarClose=document.getElementById('mpi70SidebarClose');
 const previewClose=document.getElementById('mpi70PreviewClose');
 const backdrop=document.getElementById('mpi70AdminBackdrop');
 function closeSidebar(){sidebar.classList.remove('open')}
 function syncSidebar(){const open=small()&&sidebar.classList.contains('open');document.body.classList.toggle('mpi70-sidebar-open',open);navToggle.setAttribute('aria-expanded',String(open));}
 function syncPreview(){const opened=!layout.classList.contains('noPreview');layout.classList.toggle('mpi70-preview-open',small()&&opened);previewToggle.setAttribute('aria-expanded',String(opened));}
 new MutationObserver(syncSidebar).observe(sidebar,{attributes:true,attributeFilter:['class']});
 new MutationObserver(syncPreview).observe(layout,{attributes:true,attributeFilter:['class']});
 backdrop?.addEventListener('click',closeSidebar);
 sidebarClose?.addEventListener('click',closeSidebar);
 previewClose?.addEventListener('click',()=>{if(!layout.classList.contains('noPreview'))previewToggle.click()});
 document.addEventListener('keydown',event=>{if(event.key!=='Escape')return;
  if(sidebar.classList.contains('open')){closeSidebar();navToggle.focus();}
  else if(small()&&layout.classList.contains('mpi70-preview-open')){previewToggle.click();previewToggle.focus();}
 });
 window.addEventListener('resize',()=>{if(!small())closeSidebar();syncSidebar();syncPreview()});
 // Al iniciar en celular ocultar el panel de vista previa para priorizar los formularios.
 // Esperar al inicio del editor, ya que puede estar recuperando el contenido del servidor.
 const wait=new MutationObserver(()=>{
  if(typeof previewToggle.onclick!=='function')return;
  wait.disconnect();
  if(small()&&!layout.classList.contains('noPreview'))previewToggle.click();
  syncSidebar();syncPreview();
 });
 wait.observe(document.getElementById('adminNav'),{childList:true});
 if(typeof previewToggle.onclick==='function'){
  wait.disconnect();if(small()&&!layout.classList.contains('noPreview'))previewToggle.click();syncSidebar();syncPreview();
 }
})();
