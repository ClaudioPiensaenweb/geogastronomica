# Investigacion: Seguridad en plugins WordPress

> Proyecto: GeoGastronomica | Area: seguridad | Prioridad: alta

## Resumen ejecutivo

Un plugin de banners publicitarios maneja tres vectores de ataque criticos: datos de empresa (XSS via texto), URLs de enlace (open redirect / javascript: injection) e imagenes subidas (arbitrary file upload). Las cuatro defensas obligatorias son: nonces en formularios, `current_user_can()` en cada accion, sanitizacion en entrada y escapado contextual en salida. Sin estas cuatro capas, el plugin puede ser explotado incluso por usuarios autenticados de bajo nivel.

## Hallazgos clave

1. **Nonces son obligatorios en todo formulario y accion AJAX.** CSRF sigue siendo el vector mas comun en plugins WordPress. Sin nonce, cualquier pagina externa puede disparar acciones como guardar, eliminar o modificar anuncios si el admin tiene sesion activa. Patron minimo: `wp_nonce_field('geo_save_ad', 'geo_nonce')` en el form + `check_admin_referer('geo_save_ad', 'geo_nonce')` antes de procesar.

2. **Capabilities antes que nonces.** La verificacion de permisos debe ser el primer check, antes incluso del nonce. Para este plugin el minimo es `current_user_can('edit_posts')` o una capability custom. Sin esto, un suscriptor podria ejecutar acciones si consigue el nonce.

3. **Sanitizacion por tipo de dato, no una sola funcion para todo.**
   - Texto plano (nombre empresa, descripcion): `sanitize_text_field()`
   - Email: `sanitize_email()`
   - URLs de enlace destino: `esc_url_raw()` al guardar en BD (elimina `javascript:` y protocolos peligrosos)
   - HTML permitido limitado: `wp_kses()` con allowlist explicita
   - Enteros (prioridad, slots): `absint()` o `intval()`
   - Fechas: `sanitize_text_field()` + validacion con `DateTime`

4. **Escapado contextual en output, siempre.** Regla: la funcion de escape depende del contexto HTML donde se imprime, no del tipo de dato.
   - Dentro de atributos HTML: `esc_attr()`
   - URLs en `href` o `src`: `esc_url()` (no `esc_url_raw()`)
   - Texto visible: `esc_html()`
   - HTML rico (si se permite): `wp_kses_post()`
   - Mezclar contextos (ej: texto en atributo) es el error mas frecuente.

5. **`$wpdb->prepare()` es obligatorio para queries custom.** El plugin necesitara queries para filtrar anuncios activos por zona, fecha y prioridad. Nunca interpolar variables en SQL directamente. Patron correcto:
   ```php
   $wpdb->get_results( $wpdb->prepare(
       "SELECT * FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %d",
       'geo_zone', $zone_id
   ) );
   ```
   Nota: `%s` en `prepare()` no escapa para HTML — el escapado en output es independiente.

6. **Subida de imagenes via Media Library, no upload custom.** Usar `wp_handle_upload()` + `wp_insert_attachment()` delega la validacion de MIME type, tamano y ubicacion a WordPress core. No implementar upload propio. Verificar siempre `current_user_can('upload_files')` antes de procesar.

7. **Vulnerabilidades recientes confirman el riesgo.** CVE-2025-8720 (XSS autenticado en readme parser), plugins con CSRF documentados por NinTechNet en 2025: todos comparten el patron de omitir nonce o capability check en al menos un endpoint. El patron "solo lo usa el admin" no es excusa — los admins pueden ser victimas de CSRF.

## Recomendacion

Implementar las cuatro capas en este orden de prioridad:
1. **Capability check** al inicio de cada callback (`add_action` de save_post, AJAX, REST)
2. **Nonce** en todos los formularios y endpoints de escritura
3. **Sanitizacion** en entrada, por tipo de dato especifico
4. **Escapado contextual** en cada punto de output en templates

Para este plugin: crear una funcion helper `geo_verify_request(string $action)` que encapsule el check de capability + nonce en una sola llamada, evitando que se olvide en algun callback futuro.

## Alternativas consideradas

| Opcion | Pros | Contras |
|--------|------|---------|
| Usar `sanitize_text_field()` para todo | Simple, un solo patron | No protege URLs (no elimina `javascript:`), no valida emails ni enteros |
| `wp_kses_post()` para todos los campos de texto | Permite HTML rico | Excesivo para campos como nombre empresa; permite mas de lo necesario |
| Upload custom sin `wp_handle_upload()` | Control total | Debe reimplementar validacion de MIME, extension, tamano — facil omitir casos |
| Nonce por formulario vs nonce por accion | Por accion es mas granular | Por formulario es aceptable si cada formulario tiene una sola accion |

## Referencias

- [Securely developing plugins and themes — Learn WordPress](https://learn.wordpress.org/lesson/securely-developing-plugins-and-themes/)
- [Extending WordPress: common security vulnerabilities — Learn WordPress](https://learn.wordpress.org/tutorial/extending-wordpress-common-security-vulnerabilities/)
- [Building Secure WordPress Plugins — Krasen Slavov](https://krasenslavov.com/building-secure-wordpress-plugins-best-practices-for-data-protection/)
- [WordPress Security Functions — Voxfor](https://www.voxfor.com/understanding-wordpress-security-functions/)
- [How to Secure File Uploads in WordPress — Voxfor](https://www.voxfor.com/how-to-secure-file-uploads-in-wordpress-and-block-unauthorized-files/)
- [The importance of $wpdb->prepare() — Koddr.io](https://blog.koddr.io/importance-wpdb-prepare-wordpress/)
- [25 WordPress plugins vulnerable to CSRF — NinTechNet](https://blog.nintechnet.com/25-wordpress-plugins-vulnerable-to-csrf-attacks/)
- [XSS Prevention in WordPress — PentestTesting](https://www.pentesttesting.com/xss-prevention-in-wordpress/)
