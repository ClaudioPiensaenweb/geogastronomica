# Investigacion: Uninstall limpio y migracion desde JetEngine CCT

> Proyecto: GeoGastronomica | Area: patrones | Prioridad: baja

## Resumen ejecutivo

Usar `uninstall.php` (no el hook) es el estandar actual para limpieza al desinstalar. La migracion desde JetEngine CCT requiere leer directamente de `wp_jet_cct_{slug}` con `$wpdb` e insertar en el CPT nuevo con `wp_insert_post` — no hay herramienta oficial que lo automatice, pero el proceso es directo. Dado que los datos de banners son simples (textos, URLs, fechas), la migracion se puede hacer con un script de una sola ejecucion.

## Hallazgos clave

1. **uninstall.php es preferido sobre register_uninstall_hook()** — WordPress carga el archivo de plugin completo cuando usa el hook, lo que puede ejecutar codigo global no deseado. `uninstall.php` se ejecuta de forma aislada. Debe verificar `WP_UNINSTALL_PLUGIN` al inicio o abortara por seguridad.

2. **Estructura de tabla JetEngine CCT** — Cada CCT crea una tabla `wp_jet_cct_{slug}`. Los items ocupan una fila por registro (a diferencia de wp_postmeta). Los campos son columnas directas: no hay serializado por defecto, salvo campos tipo repeater. Para los banners de zaragoza-ciudad.com la tabla sera algo como `wp_jet_cct_anuncios` con columnas para cada campo definido en el CCT.

3. **Limpieza completa en uninstall requiere borrar en orden**: (a) posts del CPT con `get_posts` + `wp_delete_post`, (b) postmeta huerfano con `DELETE FROM wp_postmeta WHERE post_id NOT IN (SELECT ID FROM wp_posts)`, (c) opciones del plugin con `delete_option`, (d) tablas custom con `DROP TABLE IF EXISTS` via `$wpdb->query`. No hay funcion nativa de WordPress que haga todo esto automaticamente.

4. **Script de migracion: $wpdb directo es el camino mas seguro** — `$wpdb->get_results("SELECT * FROM {$wpdb->prefix}jet_cct_{slug}")` devuelve los datos. Luego `wp_insert_post()` crea el CPT nuevo y `update_post_meta()` guarda cada campo. WP-CLI permite ejecutar scripts PHP de migracion con `wp eval-file migration.php` sin necesidad de instalar nada adicional.

5. **Opcion de mantener datos al desactivar (no al desinstalar)** — La desactivacion no debe borrar datos. El borrado solo ocurre en uninstall. Practica recomendada: anadir una opcion en ajustes del plugin "Borrar todos los datos al desinstalar [checkbox]" para que el admin confirme explicitamente.

## Recomendacion

Implementar `uninstall.php` con verificacion de `WP_UNINSTALL_PLUGIN` y borrado en cascada: posts del CPT, postmeta, opciones y cualquier tabla custom. Anadir checkbox de confirmacion en ajustes para evitar borrado accidental.

Para la migracion: escribir un script PHP de una sola ejecucion que corra via `wp eval-file`. No usar plugins de importacion de terceros — la estructura de datos es simple y el control total sobre el mapeo de campos es preferible. Ejecutar en entorno de staging primero.

## Alternativas consideradas

| Opcion | Pros | Contras |
|--------|------|---------|
| `uninstall.php` | Aislado, estandar actual de WP, no ejecuta codigo global | Requiere archivo adicional en el plugin |
| `register_uninstall_hook()` | Todo en un archivo | Carga el plugin completo, puede ejecutar codigo global no deseado |
| Script PHP via `wp eval-file` | Control total, sin dependencias, reversible | Requiere acceso SSH/WP-CLI al servidor |
| WP All Import / CSV importer | GUI amigable | Dependencia de plugin de tercero, coste, overkill para migracion puntual |
| phpMyAdmin manual | Rapido para ver estructura | Propenso a errores, no reproducible, no escala |

## Referencias

- [Uninstall Methods — Plugin Handbook WordPress](https://developer.wordpress.org/plugins/plugin-basics/uninstall-methods/)
- [register_uninstall_hook() — Developer.WordPress.org](https://developer.wordpress.org/reference/functions/register_uninstall_hook/)
- [Custom Content Type Overview — Crocoblock](https://crocoblock.com/knowledge-base/features/custom-content-type/)
- [JetEngine CCT CRUD — GitHub Gist Crocoblock](https://gist.github.com/Crocoblock/a9be7dbb1cb05aa2741aec97757c7f72)
- [wp eval-file — WP-CLI Command Reference](https://developer.wordpress.org/cli/commands/eval-file/)
- [WordPress uninstall.php — Digging Into WordPress](https://digwp.com/2019/11/wordpress-uninstall-php/)
