# Usuarios y permisos — Plataforma V1

| Cuenta de tienda | Permiso financiero en centro | Tienda administrada | Liquidaciones |
|---|---|---|---|
| `admin` | `admin` | Completa | Completa |
| `admin` | Sin permiso | Completa | Denegada |
| `editor` | `analyst` | Edición comercial según rol | Liquidación y copias |
| `editor` | Sin permiso | Edición comercial según rol | Denegada |

Los roles `admin` y `editor` originales de la **tienda** siguen siendo la fuente única de cuentas y contraseñas. Los permisos financieros se guardan exclusivamente en `mpi_identidad.identity_grants`. Una cuenta de tienda con rol `editor` no puede mantener un permiso financiero `admin` aunque haya sido concedido previamente.

La tabla de usuarios de **V85.1** se conserva porque los respaldos y auditorías históricos la referencian. Se vincula cada identidad central a un identificador local sin copiar contraseñas. Los métodos antiguos de inicio de sesión de V85.1 quedan deshabilitados cuando se activa el SSO. La tienda pública continúa accesible sin iniciar sesión.

La autorización y la revocación se verifican mediante solicitudes servidor-a-servidor. No se comparten cookies entre subdominios. La contraseña nunca se transmite de una aplicación a otra. La sesión central caduca por inactividad de 30 minutos y a las 12 horas como máximo. Los códigos de autorización duran 90 segundos, se usan una sola vez y están protegidos por `state` y PKCE S256.

Para usuarios que ya tienen liquidaciones históricas, realiza una vinculación manual antes del primer acceso integrado. No mezcles bases ni borres cuentas antiguas sin respaldo.
