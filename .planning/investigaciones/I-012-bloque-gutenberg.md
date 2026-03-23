# Investigacion: Bloque Gutenberg para zonas de anuncios

> Proyecto: GeoGastronomica | Area: alternativas | Prioridad: baja

## Resumen ejecutivo

Crear un bloque Gutenberg dinamico es viable y complementario a los shortcodes, pero no los reemplaza. Para un plugin de banners con usuario no tecnico, el bloque aporta valor real solo como wrapper visual del shortcode en el editor. Implementarlo como bloque PHP dinamico completo requiere build tooling (@wordpress/scripts) que no encaja con la arquitectura PHP nativa del proyecto. La prioridad "baja" es correcta.

## Hallazgos clave

1. **Bloques dinamicos vs estaticos**: Los bloques dinamicos usan `render.php` (introducido en WP 6.1 via `block.json` → `"render": "file:./render.php"`) en lugar de `save.js`. El HTML se genera en PHP en cada carga, igual que un shortcode. Solo se guardan los atributos en la BD.

2. **`block.json` es el estandar moderno**: Desde WP 5.8, `block.json` centraliza nombre, atributos, scripts, estilos y ruta al render. `register_block_type()` acepta directamente la ruta al directorio con `block.json`. Sin este archivo, la integracion con el ecosistema WordPress (block bindings, style variations, hooks) no funciona.

3. **`ServerSideRender` esta en declive**: La documentacion oficial lo califica como "fallback o mecanismo legacy". El enfoque moderno es `render.php` + atributos declarados en `block.json`. SSR introduce latencia en el editor por llamadas REST en cada cambio de atributo.

4. **`@wordpress/scripts` es necesario para bloques reales**: Cualquier bloque con `InspectorControls` (sidebar de ajustes) requiere JSX/ESNext compilado. El paquete `@wordpress/scripts` provee webpack zero-config. Sin build step, solo es posible JS vanilla con `wp.element` global, que es verbose y limitado.

5. **`InspectorControls`** del paquete `@wordpress/block-editor` permite anadir controles en la barra lateral del editor (selects, toggles, text inputs). Para este plugin, el control util seria un `SelectControl` con las zonas disponibles (home, categoria, articulo).

6. **Bloque vs shortcode para usuario no tecnico**: Un bloque Gutenberg es mas facil de usar que un shortcode para el editor periodista: no necesita recordar ni escribir codigo. En el insertor de bloques busca "GeoAd", selecciona la zona y el bloque se renderiza con preview. Sin embargo, los shortcodes siguen siendo necesarios para widgets, templates PHP y page builders.

7. **`register_block_type()` en PHP**: Se llama en el hook `init`. Si se usa `block.json`, basta con `register_block_type( __DIR__ . '/blocks/geoad' )`. El render callback puede ser una funcion PHP o la propiedad `render` en `block.json`.

## Recomendacion

No implementar bloque Gutenberg en la fase inicial (MVP). Mantener shortcodes como mecanismo principal segun lo definido en el briefing. Si se aborda en una fase posterior (alineado con el "Could have" del briefing), implementar como bloque dinamico minimalista:

- `block.json` con atributo `zone` (string)
- `render.php` que llama internamente a la misma funcion que el shortcode
- `edit.js` compilado con `@wordpress/scripts` con un `SelectControl` para elegir zona
- Sin `ServerSideRender` — el edit muestra un placeholder estatico con el nombre de la zona

Este enfoque reutiliza el 100% de la logica PHP existente del shortcode.

## Alternativas consideradas

| Opcion | Pros | Contras |
|--------|------|---------|
| Bloque dinamico completo (`block.json` + `render.php` + `edit.js`) | Experiencia nativa en Gutenberg, sin shortcodes | Requiere build step con Node.js, complejidad anadida |
| Shortcode block (wrapper minimo) | Sin build step, reutiliza shortcode existente, < 50 lineas JS | Preview en editor muestra placeholder, no el banner real |
| `ServerSideRender` | Preview real en editor | Deprecado como enfoque principal, latencia en cada cambio |
| Solo shortcodes (decision actual) | Sin dependencias JS, compatible con cualquier contexto | Editor debe recordar sintaxis, menos UX |

## Referencias

- [Creating dynamic blocks — WordPress Developer Docs](https://developer.wordpress.org/block-editor/how-to-guides/block-tutorial/creating-dynamic-blocks/)
- [block.json — Block Editor Handbook](https://developer.wordpress.org/block-editor/getting-started/fundamentals/block-json/)
- [@wordpress/server-side-render — nota de deprecacion](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-server-side-render/)
- [register_block_type() — referencia de funcion](https://developer.wordpress.org/reference/functions/register_block_type/)
- [Inspector Controls — Misha Rudrastyh](https://rudrastyh.com/gutenberg/inspector-controls.html)
- [Block.json & Server-side Registration — WordPress VIP](https://wpvip.com/2023/02/08/block-json-server-side-registration/)
