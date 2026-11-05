<?php
 declare(strict_types=1);
 require dirname(__DIR__,2).'/app/bootstrap.php';
 sessionStart();if(!currentUser()){header('Location: ./index.php');exit;}
 header('Content-Type: text/html; charset=utf-8');header('Cache-Control: private, no-store');
 header('X-Frame-Options: SAMEORIGIN');header('X-Content-Type-Options: nosniff');
 $html=file_get_contents(dirname(__DIR__,2).'/app/editor_template.html');
 $html=str_replace('__MPI_LOGOUT_CSRF__',h(csrfToken()),$html);
 $html=str_replace('__MPI_USERS_LINK__', (currentUser()['role']??'')==='admin' ? '<a class="btn ghost" href="admin/users.php" title="Crear usuarios, gestionar roles y contraseñas">👥 Usuarios</a>' : '', $html);
 if(mpi_sso_enabled()){$link='<a class="btn ghost" href="'.h(rtrim(mpi_sso_config()['auth_base_url'],'/')).'/index.php" target="_blank" rel="noopener noreferrer">🏢 Centro de operaciones</a>';$html=str_replace('__MPI_PORTAL_LINK__',$link,$html);}else{$html=str_replace('__MPI_PORTAL_LINK__','',$html);}
 $csrf=json_encode(csrfToken(),JSON_HEX_TAG|JSON_HEX_AMP|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_THROW_ON_ERROR);
 $role=json_encode((string)(currentUser()['role']??'editor'),JSON_THROW_ON_ERROR);
 $prefix=rtrim(str_replace('\\','/',dirname(dirname($_SERVER['SCRIPT_NAME']))),'/').'/';
 $code='<base href="'.h($prefix).'"><script>window.MPI_CMS_SERVER=true;window.MPI_CMS_CSRF='.$csrf.';window.MPI_CMS_ROLE='.$role.';</script>'; 
 echo str_replace('<head>', '<head>'.$code, $html);
