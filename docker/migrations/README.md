# Migraciones de Mega Prime Import

El contenedor aplica automáticamente:

1. Los esquemas base (solo si no están registrados en `schema_migrations`):
   - `tienda/schema.sql`
   - `acceso/schema.sql`
   - `liquidaciones/private/schema.sql`
   - `liquidaciones/private/identity_links.sql`
2. Los archivos `.sql` de esta carpeta, en orden alfabético.

## Nombre obligatorio

```
<alcance>__<descripcion>.sql
```

Alcances permitidos:

| Prefijo | Base donde se aplica |
|---|---|
| `tienda__` | Base de la tienda |
| `identidad__` | Base de identidad (SSO) |
| `liquidaciones__` | Base de liquidaciones |
| `all__` | Las tres bases (o la única base en modo single) |

Ejemplos:

```
tienda__002_add_sku_column.sql
liquidaciones__003_add_index.sql
all__004_add_common_table.sql
```

## Reglas

- Cada archivo se aplica **una sola vez** por base; se registra en `schema_migrations`.
- No edites una migración ya aplicada: crea un archivo nuevo.
- Usa SQL idempotente cuando sea posible. MySQL/MariaDB hace `COMMIT` implícito en DDL, así que una migración que falla a mitad puede dejar parte aplicada.
- El divisor de sentencias reconoce `;` seguido de salto de línea o fin de archivo. Evita procedimientos almacenados con `;` internos.
- Haz respaldo de la base antes de una migración destructiva (`DROP`, `MODIFY`, cambios de tipo).
- Para rollback, restaura el respaldo y vuelve a desplegar el commit anterior. Las migraciones no se ejecutan en reversa automáticamente.
