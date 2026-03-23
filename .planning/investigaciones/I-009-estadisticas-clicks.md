# Investigacion: Estadisticas de impresiones y clicks para anuncios

> Proyecto: GeoGastronomica | Area: librerias | Prioridad: media (Could have)

## Resumen ejecutivo

Para un plugin WordPress autocontenido con un unico usuario administrador, el tracking de stats es viable sin dependencias externas. La combinacion optima es: Beacon API con fallback a REST API para captura, tabla custom `$wpdb` para almacenamiento, y un dashboard widget simple para visualizacion. El GDPR es manejable porque las stats se basan en conteos agregados sin datos personales ni cookies.

## Hallazgos clave

1. **Beacon API vs AJAX vs REST API para captura**
   - `navigator.sendBeacon()` es la opcion ideal para tracking: no bloquea el render, funciona tras navegacion, no requiere respuesta del servidor. Soporte universal en browsers modernos (Chrome, Firefox, Edge, Safari 11.1+).
   - REST API es 30% mas rapida que `admin-ajax.php` porque no carga WordPress completo. `admin-ajax.php` instancia WordPress entero en cada peticion — inaceptable para tracking de alto volumen.
   - Patron recomendado: `sendBeacon()` apuntando al endpoint REST API propio del plugin (`/wp-json/geogastronomica/v1/track`). Fallback a `fetch()` si Beacon no disponible.
   - Impresiones: registrar en `DOMContentLoaded` o con IntersectionObserver (solo si el banner es visible). Clicks: listener en el enlace del banner antes de navegar.

2. **Tabla custom vs post meta para almacenar stats**
   - Post meta es inadecuado para datos de alta frecuencia: la tabla `wp_postmeta` crece descontroladamente y las queries con JOINs se degradan severamente (>5s en datasets grandes vs <1.5s con tabla custom).
   - Tabla custom dedicada es la eleccion correcta. Esquema minimo:
     ```sql
     CREATE TABLE {prefix}geoad_stats (
       id         BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
       ad_id      BIGINT UNSIGNED NOT NULL,
       event_type ENUM('impression','click') NOT NULL,
       zone       VARCHAR(100) NOT NULL,
       created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
       INDEX (ad_id, event_type, created_at)
     );
     ```
   - Creada en `register_activation_hook` con `dbDelta()`, eliminada en `register_uninstall_hook` (uninstall limpio requerido por el briefing).
   - Para volumenes bajos (blog personal), esta tabla nunca sera un problema de rendimiento.

3. **wp_cron para agregacion periodica**
   - Registrar un evento diario con `wp_schedule_event` para agregar los registros raw en una tabla de totales (`geoad_stats_daily`), o bien en una option de WordPress por anuncio.
   - Alternativa mas simple para este caso de uso: mantener contadores acumulados en `wp_options` o en una columna de la tabla de stats agregadas, y escribir los eventos raw solo durante 30 dias. Un cron diario purga eventos antiguos y actualiza los totales. Esto evita que la tabla crezca indefinidamente.
   - Advertencia sobre `wp_cron`: solo se dispara cuando hay una visita. En sitios con poco trafico puede retrasarse. Para un blog personal es aceptable; si se necesita precision, configurar un cron real del sistema operativo llamando a `wp-cron.php`.

4. **Dashboard widget para mostrar stats**
   - `wp_add_dashboard_widget()` permite anadir un widget al escritorio de WordPress sin logica compleja.
   - Mostrar: impresiones totales y clicks totales por anuncio en los ultimos 30 dias, con CTR calculado. Una tabla HTML simple es suficiente para el usuario no tecnico.
   - Alternativamente, anadir una columna "Stats" al listado del CPT de anuncios (via `manage_posts_columns`) mostrando impresiones/clicks inline. Mas visible que un widget separado.

5. **GDPR — consideraciones**
   - El tracking basado en conteos agregados (sin IP, sin fingerprint, sin cookies de seguimiento) no requiere consentimiento explicito bajo GDPR en la mayoria de interpretaciones, ya que no procesa datos personales.
   - NO almacenar: IP del visitante, User Agent completo, cookies identificativas.
   - SI almacenar: ad_id, tipo de evento, zona, timestamp. Estos son datos de uso del propio plugin, no datos personales del visitante.
   - Anadir una linea en la Politica de Privacidad del sitio indicando que se registran estadisticas anonimas de visualizacion de anuncios propios. Con esto el riesgo legal es minimo.
   - Si en el futuro se quiere segmentar por dispositivo: usar solo el header `User-Agent` para detectar mobile/desktop/tablet y descartar el valor raw — guardar solo la categoria inferida.

## Recomendacion

Implementar tracking minimalista en dos fases:

**Fase 1 (MVP):** Endpoint REST API en el plugin + `sendBeacon()` en el JS de banners. Tabla custom `geoad_stats` con campos minimos. Cron diario que agrega totales a `wp_options` y purga raw events de mas de 30 dias. Columna "Stats" en el listado del CPT mostrando impresiones y clicks del mes.

**Fase 2 (opcional):** Dashboard widget con grafica simple si el usuario lo demanda. Exportacion CSV de stats por anuncio.

No implementar tracking sincronico ni bloquear el click del usuario esperando respuesta del servidor. El `sendBeacon` garantiza que el evento se envia sin impactar la experiencia.

## Alternativas consideradas

| Opcion | Pros | Contras |
|--------|------|---------|
| Post meta para stats | Sin tabla extra, API nativa WP | Degrada wp_postmeta, JOIN lento, no apto para escrituras frecuentes |
| admin-ajax.php | Familiar, simple | Carga WP completo en cada ping, impacto en rendimiento |
| Plugin externo (MonsterInsights, etc.) | Ya hecho | Dependencia externa, overkill para stats de banners propios, no se integra con el CPT |
| Almacenar IP para deduplicar | Precision mayor | Datos personales, requiere consentimiento GDPR, complejidad innecesaria |
| Tabla custom + REST API + Beacon | Rendimiento optimo, GDPR-safe, autocontenido | Requiere implementar dbDelta y endpoint propio (esfuerzo medio) |

## Referencias

- [WP REST API vs admin-ajax.php — Delicious Brains](https://deliciousbrains.com/comparing-wordpress-rest-api-performance-admin-ajax-php/)
- [Beacon API — Smashing Magazine](https://www.smashingmagazine.com/2018/07/logging-activity-web-beacon-api/)
- [Custom tables vs post meta — Sarathlal N](https://sarathlal.com/scaling-wordpress-custom-tables-postmeta-bottleneck/)
- [Do not use post meta — Medium](https://medium.com/write-better-wordpress-code/do-not-use-post-meta-fec12a7661)
- [WordPress Privacy Compliance 2026 — HeyReliable](https://heyreliable.com/wordpress-privacy-compliance-2025/)
- [WordPress Cron Job Guide — WPZOOM](https://www.wpzoom.com/blog/wordpress-cron-job/)
