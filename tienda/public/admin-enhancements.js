/* V85: avisa antes de cerrar sesión si hay cambios locales no publicados. */
(()=>{'use strict';
const form=document.getElementById('mpi85LogoutForm');
if(!form)return;
form.addEventListener('submit',event=>{
  const label=document.getElementById('unsavedLabel');
  if(label?.classList.contains('dirty')&&!window.confirm('Hay cambios sin guardar. ¿Deseas cerrar la sesión y descartarlos?'))event.preventDefault();
});
})();
