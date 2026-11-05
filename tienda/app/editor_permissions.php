<?php
/** Compara cambios que un editor comercial NO tiene autorización para publicar.
 * Función pura para poder probar permisos sin depender de MariaDB.
 */
declare(strict_types=1);
function mpiEditorProtectedChange(array $before, array $after): ?string {
    foreach (['calcRules','technicalProfiles'] as $key) {
        if (json_encode($before[$key]??[], JSON_UNESCAPED_UNICODE)
            !== json_encode($after[$key]??[], JSON_UNESCAPED_UNICODE)) {
            return 'Solo el administrador puede modificar reglas y medidas técnicas.';
        }
    }
    $protected=[
      ['quotePrint','colors','Los colores de impresión solo puede cambiarlos el administrador.'],
      ['demoMode',null,'Solo el administrador puede activar o desactivar el modo DEMO.'],
      ['seo','indexable','Solo el administrador puede autorizar la indexación pública.'],
    ];
    foreach ($protected as [$section,$field,$message]) {
      $prior=$field===null?($before[$section]??null):($before[$section][$field]??null);
      $next=$field===null?($after[$section]??null):($after[$section][$field]??null);
      if (json_encode($prior, JSON_UNESCAPED_UNICODE)
          !== json_encode($next, JSON_UNESCAPED_UNICODE))return $message;
    }
    return null;
}
