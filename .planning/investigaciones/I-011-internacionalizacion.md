# Investigacion: Internacionalizacion del plugin WordPress

> Proyecto: GeoGastronomica | Area: patrones | Prioridad: baja

## Resumen ejecutivo

Preparar GeoGastronomica para traduccion futura es bajo coste si se aplica desde el inicio: todas las cadenas de texto pasan por funciones gettext con un text domain consistente. El workflow .pot → .po → .mo es el estandar de WordPress.org. Desde WP 6.8 (marzo 2025) `load_plugin_textdomain()` ya no es obligatorio si el plugin declara el `Text Domain` en su header — reduccion de codigo boilerplate.

## Hallazgos clave

1. **Plugin header obligatorio**: Declarar `Text Domain: geogastronomica` y `Domain Path: /languages` en el encabezado del archivo principal. WP 6.8+ carga las traducciones automaticamente si estos headers existen, sin necesitar `load_plugin_textdomain()`.

2. **Funciones de traduccion segun contexto**:
   - `__( 'texto', 'geogastronomica' )` — retorna la cadena traducida (usar en PHP donde se asigna a variable)
   - `_e( 'texto', 'geogastronomica' )` — imprime directamente (evitar; preferir `echo __()`)
   - `_n( 'singular', 'plural', $count, 'geogastronomica' )` — pluralizacion
   - `esc_html__( 'texto', 'geogastronomica' )` — traduccion + escape HTML (preferida para output en pantalla)
   - `esc_attr__( 'texto', 'geogastronomica' )` — traduccion + escape para atributos HTML
   - `_x( 'texto', 'contexto', 'geogastronomica' )` — cuando la misma palabra tiene significados distintos segun contexto

3. **Combinacion traduccion + escape**: Nunca `echo esc_html( __( 'texto', 'domain' ) )` — usar directamente `esc_html__()`. Ahorra una llamada y es el patron recomendado por WordPress Coding Standards.

4. **Workflow .pot → .po → .mo**:
   - `.pot` — plantilla generada por WP-CLI: `wp i18n make-pot . languages/geogastronomica.pot`
   - `.po` — archivo de traduccion por idioma (ej. `geogastronomica-es_ES.po`), editado con Poedit
   - `.mo` — compilado binario que WordPress carga: `wp i18n make-mo languages/geogastronomica-es_ES.po`
   - Convencion de nombre: `{text-domain}-{locale}.po` (ej. `geogastronomica-es_ES.po`)

5. **Comentarios para traductores**: Incluir comentarios `/* translators: %s es el nombre de la empresa */` justo encima de strings con placeholders. WP-CLI los extrae automaticamente al .pot.

6. **translate.wordpress.org**: Si el plugin se publica en WordPress.org, las traducciones se gestionan comunitariamente ahi. Para uso privado (este caso), basta con entregar los archivos .po/.mo en la carpeta `/languages`.

7. **Variables en strings**: Nunca concatenar — usar placeholders: `sprintf( __( 'Banner de %s', 'geogastronomica' ), $empresa )`.

## Recomendacion

Aplicar i18n desde el inicio con coste casi nulo: definir el text domain en el header del plugin, usar `esc_html__()` y `esc_attr__()` en todos los strings de salida, y ejecutar `wp i18n make-pot` al finalizar el desarrollo. No publicar en wordpress.org en esta fase — entregar `.pot` + `.po` para es_ES en la carpeta `/languages`.

No es necesario implementar `load_plugin_textdomain()` si se requiere WP 6.x+ (ya cubierto por el briefing).

## Alternativas consideradas

| Opcion | Pros | Contras |
|--------|------|---------|
| Sin i18n (hardcoded en es_ES) | Cero overhead inicial | Deuda tecnica: reescribir todos los strings si se quiere traducir |
| i18n completo desde inicio | Preparado para traduccion, sigue WCS, coste minimo | Requiere disciplina en cada string nuevo |
| Usar Loco Translate (plugin) | GUI para gestionar .po/.mo sin CLI | Dependencia de plugin externo; WP-CLI es suficiente |

## Referencias

- [How to Internationalize Your Plugin — WordPress Developer Handbook](https://developer.wordpress.org/plugins/internationalization/how-to-internationalize-your-plugin/)
- [Internationalization — Common APIs Handbook](https://developer.wordpress.org/apis/internationalization/)
- [esc_html__() Reference](https://developer.wordpress.org/reference/functions/esc_html__/)
- [esc_attr__() Reference](https://developer.wordpress.org/reference/functions/esc_attr__/)
- [I18n improvements in WP 6.8 — Make WordPress Core](https://make.wordpress.org/core/2025/03/12/i18n-improvements-6-8/)
- [I18n for WordPress Developers — Codex](https://codex.wordpress.org/I18n_for_WordPress_Developers)
