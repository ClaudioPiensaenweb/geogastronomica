# Investigacion: Shortcodes y renderizado frontend para banners

> Proyecto: GeoGastronomica | Area: patrones | Prioridad: alta

## Resumen ejecutivo

El Shortcode API de WordPress es la via correcta para insertar zonas de anuncios desacopladas de cualquier page builder. La practica actual recomendada es siempre retornar un string (nunca hacer `echo` directo), usar output buffering solo cuando se incluyen templates con `echo`, cargar CSS/JS condicionalmente con `has_shortcode()` o enqueuando dentro del callback, y cachear el output con Transients cuando la query sea costosa. Los atributos deben sanitizarse en entrada y escaparse en salida.

## Hallazgos clave

1. **Return, nunca echo directo.** El Plugin Handbook de WordPress establece que los shortcodes son filtros — hacer echo produce output en el lugar equivocado de la pagina. Siempre retornar una cadena. Si el template usa echo internamente, capturarlo con `ob_start()` / `ob_get_clean()`.

2. **Enqueue condicional: dos patrones validos.**
   - `has_shortcode( get_the_content(), 'geoad' )` dentro del hook `wp_enqueue_scripts` — funciona solo cuando hay un post en contexto (home, singular). Tiene el problema de que `get_the_content()` no esta disponible en todos los contextos (widgets, sidebars).
   - Patron register + enqueue diferido: registrar con `wp_register_script` / `wp_register_style` en `init`, y llamar a `wp_enqueue_script` / `wp_enqueue_style` dentro del propio callback del shortcode. WordPress 3.3+ soporta enqueue dentro de shortcodes para JS en footer. Para CSS en `<head>` se recomienda el patron `has_shortcode` o registrar y dejar que el shortcode haga el enqueue (WordPress lo coloca correctamente si aun no se ha enviado el header).

3. **Cache con Transients API.** La query de anuncios activos por zona (filtros de fecha, prioridad, zona) puede ser costosa. Patron recomendado: `get_transient("geoad_zone_{$zone}")` al inicio del callback; si es false, ejecutar la query, guardar con `set_transient()` con TTL de 1 hora. Invalidar el transient en `save_post` del CPT de anuncios. Esto elimina queries repetidas en paginas de alto trafico.

4. **Atributos: shortcode_atts + sanitizar + escapar.** Usar `shortcode_atts()` para definir defaults y filtrar atributos no declarados. Sanitizar cada atributo segun su tipo (`sanitize_key()` para `zone`, `intval()` para numeros). Escapar en output con `esc_url()` para URLs, `esc_attr()` para atributos HTML, `esc_html()` para texto. El Wordfence report de 2023 identifico XSS en mas de 100 plugins por no escapar atributos de shortcode en output dinamico dentro de atributos HTML.

5. **CLS y reserva de espacio.** Para cumplir el objetivo CLS < 0.1 del briefing, el contenedor del banner debe tener dimensiones fijas definidas por CSS antes de que la imagen cargue. El shortcode debe renderizar el wrapper con `width` y `height` o `aspect-ratio` aplicado via clase CSS segun el formato de la zona (vertical, cuadrado, horizontal, movil).

## Recomendacion

Usar el patron **register en init + enqueue dentro del callback** para CSS y JS. Es el mas robusto porque funciona en cualquier contexto (post content, widgets, shortcodes en templates PHP). Combinar con **Transients API** con TTL de 1 hora e invalidacion en `save_post`. Estructura del shortcode:

```php
function geoad_shortcode( $atts ) {
    $atts = shortcode_atts( [ 'zone' => '' ], $atts, 'geoad' );
    $zone = sanitize_key( $atts['zone'] );
    if ( empty( $zone ) ) return '';

    wp_enqueue_style( 'geoad-frontend' );
    wp_enqueue_script( 'geoad-rotation' );

    $cache_key = 'geoad_zone_' . $zone;
    $output    = get_transient( $cache_key );
    if ( false === $output ) {
        $output = geoad_render_zone( $zone ); // query + render
        set_transient( $cache_key, $output, HOUR_IN_SECONDS );
    }
    return $output; // nunca echo
}
add_shortcode( 'geoad', 'geoad_shortcode' );
```

Invalidacion del cache:
```php
add_action( 'save_post_geoad_banner', function( $post_id ) {
    // invalidar todos los transients de zonas afectadas
    $zones = get_post_meta( $post_id, '_geoad_zones', true );
    foreach ( (array) $zones as $zone ) {
        delete_transient( 'geoad_zone_' . sanitize_key( $zone ) );
    }
});
```

## Alternativas consideradas

| Opcion | Pros | Contras |
|--------|------|---------|
| `has_shortcode()` en `wp_enqueue_scripts` | Enqueue limpio antes del render | No funciona en widgets ni templates PHP directos; requiere `get_the_content()` disponible |
| Echo directo en shortcode | Ninguno real | Output en lugar incorrecto; incompatible con `do_shortcode()` anidado |
| Sin cache (query en cada request) | Datos siempre frescos | Violaria objetivo de max 2 queries por pagina del briefing con multiples zonas |
| Object cache (Redis/Memcached) | Mas rapido que transients en BD | Depende de infraestructura del hosting; Transients funciona en cualquier WordPress |

## Referencias

- [Shortcode API — Plugin Handbook WordPress](https://developer.wordpress.org/plugins/shortcodes/)
- [shortcode_atts() — Developer.WordPress.org](https://developer.wordpress.org/reference/functions/shortcode_atts/)
- [Transients API — Common APIs Handbook](https://developer.wordpress.org/apis/transients/)
- [Conditional Scripts & Styles for WordPress Shortcodes — Austin Gil](https://austingil.com/conditional-scripts-styles-for-wordpress-shortcodes/)
- [How to Load a Script in WordPress if a Shortcode Exists — WPExplorer](https://www.wpexplorer.com/load-scripts-shortcode/)
- [Improve Shortcode Performance With Transients API — DAEXT](https://daext.com/blog/improve-the-shortcode-performance-with-the-wordpress-transients-api/)
- [XSS en shortcodes: 100+ plugins afectados — Wordfence 2023](https://www.wordfence.com/blog/2023/12/over-100-wordpress-repository-plugins-affected-by-shortcode-based-stored-cross-site-scripting/)
- [Validating, Sanitizing and Escaping — WordPress VIP Docs](https://docs.wpvip.com/security/validating-sanitizing-and-escaping/)
