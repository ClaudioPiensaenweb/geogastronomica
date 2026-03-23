# Investigacion: Performance y cache en plugins WordPress

> Proyecto: GeoGastronomica | Area: performance | Prioridad: baja

## Resumen ejecutivo

Para un plugin de banners con multiples shortcodes por pagina, la estrategia optima es usar **Transient API** para cachear los resultados de queries de anuncios activos por zona (TTL de 1-6 horas), combinado con encolado condicional de CSS/JS. Esto elimina las queries a BD en el 95% de las visitas sin requerir infraestructura adicional (Redis/Memcached). El objetivo del briefing de maximo 2 queries por pagina es alcanzable con una sola query consolidada + transient.

## Hallazgos clave

1. **Transient API es la opcion correcta para este proyecto.** Persiste en `wp_options` sin requerir Redis ni Memcached. Si el servidor tiene object cache persistente configurado, WordPress automaticamente usa ese backend de forma transparente sin cambios de codigo. Ideal para un plugin que debe funcionar en cualquier hosting compartido.

2. **WP Rocket no soporta fragment caching.** Cachea paginas completas como HTML estatico, por lo que shortcodes con contenido dinamico quedan "congelados". La solucion es hacer las queries en PHP normal (sin AJAX por defecto) y cachear el resultado con transients — el HTML generado queda estatico en la cache de pagina completa, que es exactamente lo que se quiere para banners (rotacion via JS en cliente, no recargas de servidor).

3. **LiteSpeed Cache con ESI** permite fragment caching real (partes de pagina con TTL propio), pero requiere hosting LiteSpeed y configuracion del plugin. No es una dependencia razonable para un plugin generico.

4. **Encolado condicional dentro del shortcode callback** es el patron correcto. Llamar `wp_enqueue_script()` y `wp_enqueue_style()` directamente desde `add_shortcode()` funciona si el shortcode aparece antes del `wp_footer`. Para mayor seguridad, usar `wp_add_inline_script` en el footer. Esto evita cargar assets en paginas sin banners.

5. **Query Monitor** es la herramienta de profiling estandar. Permite ver queries duplicadas, N+1, y tiempo de ejecucion. Umbral de "query lenta" por defecto: 0.05 segundos. Solo usar en entorno de desarrollo/staging — tiene overhead propio.

6. **Parametros de optimizacion en WP_Query para este plugin:** usar `no_found_rows => true` (elimina COUNT(*) innecesario), `ignore_sticky_posts => true`, `fields => 'ids'` si solo se necesitan IDs, y `posts_per_page` siempre explicito. Una sola query consolidada por pagina (todas las zonas de una vez) + transient por zona logra el objetivo de <= 2 queries.

7. **Invalidacion del transient:** en `save_post_{post_type}` y `trash_post` del CPT de anuncios, borrar los transients de zonas afectadas. No usar TTL muy corto (< 1h) — el caso de caducidad automatica por fecha_fin se resuelve incluyendo la fecha en la cache key o con un cron diario que limpia transients.

## Recomendacion

Implementar cache con Transient API usando este patron:

- Cache key por zona: `geoad_zone_{zone_slug}` con TTL de 3600 segundos (1 hora)
- Invalidar en `save_post_geoad` y al cambiar estado del anuncio
- Una sola query inicial para todas las zonas de la pagina si es posible, o una por zona con transient individual
- Enqueue de CSS/JS dentro del shortcode callback con `wp_enqueue_*` — WordPress deduplica automaticamente
- Reservar espacio para banners via CSS con dimensiones fijas (evita CLS) independientemente de si el banner cargo

No implementar Redis/Memcached como requisito — si el hosting lo tiene, funciona gratis via Transient API.

## Alternativas consideradas

| Opcion | Pros | Contras |
|--------|------|---------|
| Transient API (recomendada) | Sin dependencias, funciona en cualquier hosting, se beneficia de object cache si existe | Almacena en `wp_options`, puede generar bloat sin autoclean (usar `delete_expired_transients` periodicamente) |
| wp_object_cache directo | Ultra-rapido si hay Redis/Memcached | No persiste entre requests sin backend persistente, no valido para hosting compartido |
| Fragment caching con LiteSpeed ESI | Cache real por fragmento, TTL por zona | Requiere hosting LiteSpeed, dependencia de infraestructura, complejidad de configuracion |
| Sin cache (query en cada request) | Sin logica de invalidacion | Incumple el objetivo de <= 2 queries/pagina bajo carga |

## Referencias

- [WordPress Transient API — Developer Handbook](https://developer.wordpress.org/apis/transients/)
- [WP_Object_Cache — Developer Reference](https://developer.wordpress.org/reference/classes/wp_object_cache/)
- [WP Rocket: no soporta fragment caching](https://docs.wp-rocket.me/article/1536-does-wp-rocket-support-fragment-caching)
- [Query Monitor — Profiling y logging](https://querymonitor.com/wordpress-debugging/profiling-and-logging/)
- [Conditional Scripts para Shortcodes — Austin Gil](https://austingil.com/conditional-scripts-styles-for-wordpress-shortcodes/)
- [WP_Query optimizacion — Pantheon](https://pantheon.io/learning-center/performance/wp-query)
- [Transient API — Introduccion en WordPress Developer Blog](https://developer.wordpress.org/news/2024/06/an-introduction-to-the-transients-api/)
