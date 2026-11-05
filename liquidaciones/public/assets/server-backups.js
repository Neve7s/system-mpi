(function(){'use strict';
 const result=()=>document.getElementById('mpiBackupsResult');
 const token=()=>document.querySelector('#mpiBarraGuardado')?.closest('body')?.querySelector('form[action="admin.php"] input[name="_csrf"]')?.value||'';
 const msg=(s)=>{result().textContent=s;};
 async function jsonRequest(url,init={}){
  const response=await fetch(url,{credentials:'same-origin',cache:'no-store',...init});
  if(response.status===401){location.assign('admin.php');throw Error('Sesión caducada');}
  const data=await response.json();if(!response.ok)throw Error(data.error||'Error de servidor');return data;
 }
 window.mpiGuardarEnServidor=async()=>{
  try{
   if(typeof premiumCapturarCopia!=='function'||typeof premiumValidarCopia!=='function')throw Error('El módulo de respaldo no está disponible.');
   const label=document.getElementById('mpiBackupLabel').value.trim();if(!label)throw Error('Indica un nombre de copia.');
   const copy=premiumCapturarCopia(label);premiumValidarCopia(copy);
   const res=await jsonRequest('copias.php',{method:'POST',headers:{'Content-Type':'application/json','X-CSRF-Token':token()},body:JSON.stringify(copy)});
   msg('Copia guardada en MariaDB: '+res.label+' · ID '+res.id);await mpiListarCopiasServidor();
  }catch(e){msg('No se guardó: '+e.message);}
 };
 window.mpiListarCopiasServidor=async()=>{
  try{
   const data=await jsonRequest('copias.php');const list=document.createElement('ul');
   for(const c of data.items){const li=document.createElement('li');li.append(document.createTextNode(`${c.label} · ${c.created_at} · ${c.author} · `));
    const b=document.createElement('button');b.type='button';b.textContent='Previsualizar y restaurar';b.addEventListener('click',()=>mpiObtenerCopiaServidor(c.id));li.append(b);list.append(li);}
   result().replaceChildren(document.createTextNode('Copias en servidor (máximo 50 recientes):'),list);
  }catch(e){msg('No se pudieron listar: '+e.message);}
 };
 window.mpiObtenerCopiaServidor=async(id)=>{
  try{
   const d=await jsonRequest('copias.php?id='+encodeURIComponent(id));
   // La función original valida la estructura y exige confirmación antes de restaurar.
   premiumPrevisualizar(d,'Copia MariaDB '+id);
  }catch(e){msg('No se pudo recuperar: '+e.message);}
 };
})();
