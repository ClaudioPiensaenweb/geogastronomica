# Roadmap — GeoGastronomica

> Generado por piensa v2.0.0
> Fecha: 2026-03-23 | Version del plan: 1.0.0

---

## Stack del proyecto

| Capa | Tecnologia |
|------|-----------|
| Frontend | PHP shortcodes + JS vanilla + CSS responsive |
| Backend | WordPress Plugin (PHP 8.0+ OOP) |
| Base datos | WordPress wpdb (CPT + post_meta + tabla custom stats) |
| Testing | PHPUnit + Brain Monkey + @wordpress/env |
| Runtime | WordPress 6.x+ |
| Package Manager | Composer (classmap) |
| Updates | plugin-update-checker v5.6 (GitHub Releases) |
| i18n | gettext nativo WP 6.8+ |

---

## Trazabilidad: Requisito -> Tareas

| Requisito | Tareas |
|-----------|--------|
| RF-01: CPT "Anuncio" con registro propio | T-002 |
| RF-02: Meta boxes organizados en tabs | T-003 |
| RF-03: Campos info empresa (nombre, email, telefono) | T-003 |
| RF-04: Campos anuncio (descripcion, enlace, banners) | T-003 |
| RF-05: 4 formatos de banner (vertical, cuadrado, horizontal, movil) | T-003 |
| RF-06: Fechas inicio/fin de campana | T-003 |
| RF-07: Zonas de aparicion (home, categoria, subcategoria/articulo/autor) | T-003 |
| RF-08: Slots por zona y prioridad numerica | T-003 |
| RF-09: Shortcodes para insertar zonas de anuncios | T-004 |
| RF-10: Responsive nativo con picture/source | T-004 |
| RF-11: Rotacion de anuncios con transicion CSS fade | T-005 |
| RF-12: Caducidad automatica por fecha_fin | T-006 |
| RF-13: Lazy loading de imagenes de banners | T-004 |
| RF-14: Columnas personalizadas en admin | T-007 |
| RF-15: Acciones en lote (activar, desactivar, eliminar) | T-007 |
| RF-16: Estadisticas basicas impresiones/clicks | T-008 |
| RF-17: Cache de queries con Transient API | T-005 |
| RF-18: Seguridad 4 capas | T-009 |
| RF-19: i18n con gettext nativo | T-010 |
| RF-20: Updates desde GitHub (plugin-update-checker) | T-011 |
| RF-21: Uninstall limpio | T-012 |
| RF-22: Estructura OOP con Composer classmap | T-001 |

---

## Fase 1 — Setup y Fundamentos

**Objetivo:** Establecer la estructura del plugin, registrar el CPT y crear los meta boxes con todos los campos necesarios. Al finalizar esta fase el editor puede crear y editar anuncios completos desde wp-admin.
**Estado:** pendiente

| ID | Etiqueta | Titulo | Estado | Dependencias |
|----|----------|--------|--------|--------------|
| T-001 | [feature] | Scaffolding del plugin y estructura OOP | revision | ninguna |
| T-002 | [feature] | Registro del CPT geo_anuncio | revision | T-001 |
| T-003 | [feature] | Meta boxes con tabs y todos los campos | revision | T-002 |

---

#### T-001 [feature] Scaffolding del plugin y estructura OOP
- **Descripcion**: Crear la estructura de directorios del plugin, archivo principal con header WordPress, composer.json con classmap autoload, clase bootstrap (Singleton) que inicializa el plugin, y constantes globales. Seguir la arquitectura recomendada en I-001: 6-7 clases con responsabilidad unica, Singleton solo para bootstrap. Usar Composer classmap para resolver la tension PSR-4 vs WPCS en nombres de archivo. Referencia: Ver `.planning/investigaciones/I-001-arquitectura-plugin-wp.md`
- **Criterios de aceptacion**:
  - [ ] [restriccion] El archivo `geogastronomica.php` tiene header valido con Plugin Name, Version, Text Domain, Requires PHP 8.0
  - [ ] [restriccion] `composer.json` define classmap autoload apuntando a `includes/`
  - [ ] [restriccion] Ejecutar `composer dump-autoload` genera `vendor/autoload.php` sin errores
  - [ ] [restriccion] La clase `GeoGastronomica` usa patron Singleton y se instancia en el archivo principal
  - [ ] [restriccion] Existen los directorios: `includes/`, `assets/css/`, `assets/js/`, `templates/`, `languages/`
  - [ ] [negativo] NO DEBE incluir dependencias externas en composer.json (solo autoload)
  - [ ] [negativo] NO DEBE activarse si PHP < 8.0 (mostrar admin_notice y desactivar)
- **Archivos afectados**: ~4 archivos
  - Crear: `geogastronomica.php`
  - Crear: `composer.json`
  - Crear: `includes/class-geogastronomica.php`
  - Crear: `uninstall.php` (placeholder)
- **Sizing**: Archivos 2, Deps 1, Claridad alta, Riesgo bajo — Score: 5/12
- **Dependencias**: ninguna
- **Estado**: revision
- **Agentes**: developer → reviewer

---

#### T-002 [feature] Registro del CPT geo_anuncio
- **Descripcion**: Crear clase `CPT_Anuncio` que registre el Custom Post Type `geo_anuncio` con labels en espanol, soporte para titulo y thumbnail, icono dashicons, y menu propio en wp-admin bajo "GeoGastronomica". El CPT no debe usar el editor nativo de WordPress (supports solo title y thumbnail). Registrar capabilities propias mapeadas a roles editor/admin. Referencia: Ver `.planning/investigaciones/I-002-cpt-meta-boxes.md`
- **Criterios de aceptacion**:
  - [ ] [happy] GIVEN plugin activado WHEN el editor accede a wp-admin THEN ve menu "GeoGastronomica" con opcion "Nuevo anuncio"
  - [ ] [happy] GIVEN CPT registrado WHEN se accede a la URL del CPT THEN WordPress no devuelve 404
  - [ ] [edge] GIVEN slug `geo_anuncio` WHEN otro plugin usa el mismo slug THEN no produce error fatal (register_post_type retorna WP_Error)
  - [ ] [restriccion] El CPT tiene `public => false`, `show_ui => true`, `show_in_menu => true`
  - [ ] [restriccion] Los labels estan en espanol e internacionalizados con `__()` / `esc_html__()`
  - [ ] [negativo] NO DEBE registrar el CPT con supports `editor` (el contenido se gestiona via meta boxes)
  - [ ] [negativo] NO DEBE aparecer en el REST API publico (`show_in_rest => false` para MVP)
- **Archivos afectados**: ~2 archivos
  - Crear: `includes/class-cpt-anuncio.php`
  - Modificar: `includes/class-geogastronomica.php`
- **Sizing**: Archivos 1, Deps 1, Claridad alta, Riesgo bajo — Score: 4/12
- **Dependencias**: T-001
- **Estado**: pendiente
- **Agentes**: tester → developer → reviewer

---

#### T-003 [feature] Meta boxes con tabs y todos los campos
- **Descripcion**: Crear clase `Meta_Boxes` que registre un meta box con 3 tabs implementadas con JS vanilla: (1) Info Empresa — nombre empresa, email, telefono; (2) Anuncio — descripcion, enlace destino, 4 campos de imagen con Media Library picker (vertical 285x627, cuadrado 285x285, horizontal 1230x350, movil 1000x400); (3) Configuracion — fecha inicio, fecha fin, zonas de aparicion (checkboxes: home, categoria, subcategoria_articulo_autor), slot numerico por zona, prioridad numerica. Todos los campos con prefijo `_geo_`. Sanitizacion en save con nonce verification. Referencia: Ver `.planning/investigaciones/I-002-cpt-meta-boxes.md` y `.planning/investigaciones/I-005-seguridad-plugins-wp.md`
- **Criterios de aceptacion**:
  - [ ] [happy] GIVEN editor editando anuncio WHEN hace click en tab "Info empresa" THEN ve campos nombre, email, telefono
  - [ ] [happy] GIVEN editor editando anuncio WHEN hace click en tab "Anuncio" THEN ve 4 botones para subir imagenes con preview y campo enlace destino
  - [ ] [happy] GIVEN editor editando anuncio WHEN hace click en boton "Subir imagen" THEN se abre el Media Library picker nativo de WordPress
  - [ ] [happy] GIVEN editor en tab "Configuracion" WHEN selecciona zonas y prioridad THEN al guardar los valores persisten correctamente
  - [ ] [edge] GIVEN campos vacios WHEN el editor publica el anuncio THEN se guarda sin error (campos opcionales)
  - [ ] [error] GIVEN nonce invalido WHEN se intenta guardar THEN el save_post no ejecuta (fallo silencioso)
  - [ ] [restriccion] Todos los meta keys usan prefijo `_geo_` (underscore inicial para ocultar de Custom Fields UI)
  - [ ] [restriccion] Sanitizacion: email con sanitize_email(), URLs con esc_url_raw(), texto con sanitize_text_field(), numeros con absint()
  - [ ] [negativo] NO DEBE cargar scripts del Media Uploader en pantallas que no sean la edicion de geo_anuncio
  - [ ] [negativo] NO DEBE almacenar URLs de imagenes — solo attachment IDs (para regenerar thumbnails)
- **Archivos afectados**: ~4 archivos
  - Crear: `includes/class-meta-boxes.php`
  - Crear: `assets/js/admin-meta-boxes.js`
  - Crear: `assets/css/admin-meta-boxes.css`
  - Modificar: `includes/class-geogastronomica.php`
- **Sizing**: Archivos 2, Deps 2, Claridad alta, Riesgo bajo — Score: 6/12
- **Dependencias**: T-002
- **Estado**: pendiente
- **Agentes**: tester → developer → reviewer

---

## Fase 2 — Core Frontend (Shortcodes, Responsive, Rotacion, Cache)

**Objetivo:** Implementar la logica de renderizado frontend: shortcodes que muestran banners responsivos con rotacion y cache. Al finalizar esta fase los banners se muestran correctamente en el sitio con rendimiento optimo.
**Estado:** pendiente

| ID | Etiqueta | Titulo | Estado | Dependencias |
|----|----------|--------|--------|--------------|
| T-004 | [feature] | Shortcode de zona con renderizado responsive y lazy loading | revision | T-003 |
| T-005 | [feature] | Rotacion JS con transicion fade y cache de consultas | revision | T-004 |
| T-006 | [feature] | Caducidad automatica y filtrado por fechas | revision | T-004 |

---

#### T-004 [feature] Shortcode de zona con renderizado responsive y lazy loading
- **Descripcion**: Crear clase `Shortcode_GeoAd` que registre el shortcode `[geoad zone="home_vertical_1"]`. El shortcode consulta anuncios activos para la zona indicada (filtrado por zona, fecha activa, ordenados por prioridad DESC), genera HTML con `<picture>` + `<source media>` para los 4 breakpoints, aplica `loading="lazy"` y reserva espacio con `aspect-ratio` para CLS < 0.1. El shortcode DEBE retornar (nunca echo). Enqueue condicional de CSS solo cuando el shortcode se usa en la pagina. Referencia: Ver `.planning/investigaciones/I-003-shortcodes-frontend.md`, `.planning/investigaciones/I-007-responsive-banners.md`
- **Criterios de aceptacion**:
  - [ ] [happy] GIVEN shortcode `[geoad zone="home_vertical_1"]` en contenido WHEN la pagina se renderiza THEN muestra el banner activo con mayor prioridad para esa zona
  - [ ] [happy] GIVEN anuncio con 4 formatos de imagen WHEN se renderiza en movil THEN solo descarga la imagen movil (picture/source)
  - [ ] [happy] GIVEN anuncio con enlace destino WHEN usuario hace click en el banner THEN navega al enlace en nueva pestana (target="_blank" rel="noopener")
  - [ ] [edge] GIVEN zona sin anuncios activos WHEN se renderiza el shortcode THEN retorna string vacio (no rompe el layout)
  - [ ] [edge] GIVEN anuncio sin imagen para un formato WHEN se renderiza THEN usa fallback al formato mas cercano disponible
  - [ ] [error] GIVEN shortcode sin atributo zone WHEN se renderiza THEN retorna comentario HTML de debug (solo si WP_DEBUG)
  - [ ] [restriccion] Las imagenes tienen `loading="lazy"` y contenedor con `aspect-ratio` definido por formato
  - [ ] [restriccion] CSS del shortcode se encola solo en paginas donde se usa (wp_enqueue_style condicional)
  - [ ] [restriccion] Maximo 2 queries a BD por zona por pagina (preparar para cache en T-005)
  - [ ] [negativo] NO DEBE usar echo dentro del callback del shortcode — siempre return
  - [ ] [negativo] NO DEBE generar CLS > 0.1 (reservar espacio antes de cargar imagen)
- **Archivos afectados**: ~4 archivos
  - Crear: `includes/class-shortcode-geoad.php`
  - Crear: `templates/banner-zone.php`
  - Crear: `assets/css/geoad-frontend.css`
  - Modificar: `includes/class-geogastronomica.php`
- **Sizing**: Archivos 2, Deps 2, Claridad alta, Riesgo medio — Score: 7/12
- **Dependencias**: T-003
- **Estado**: pendiente
- **Agentes**: tester → developer → reviewer

---

#### T-005 [feature] Rotacion JS con transicion fade y cache de consultas
- **Descripcion**: Crear script JS vanilla que rota banners cuando hay multiples anuncios activos en una zona. Usar setTimeout recursivo (no setInterval) para cambiar de banner con transicion CSS opacity fade. Implementar cache con Transient API: cachear array de IDs de anuncios activos por zona, invalidar en save_post del CPT. El shortcode debe generar markup con todos los banners de la zona (ocultos) y el JS los rota. Usar `no_found_rows => true` en WP_Query para rendimiento. Referencia: Ver `.planning/investigaciones/I-008-rotacion-prioridad.md`, `.planning/investigaciones/I-014-performance-cache.md`
- **Criterios de aceptacion**:
  - [ ] [happy] GIVEN zona con 3 anuncios activos WHEN pasan N segundos THEN el banner cambia con transicion fade al siguiente por prioridad
  - [ ] [happy] GIVEN zona con 1 solo anuncio WHEN se renderiza THEN muestra el anuncio sin activar rotacion JS
  - [ ] [happy] GIVEN cache frio (primer request) WHEN se consultan anuncios THEN se crea transient con IDs y se sirve resultado
  - [ ] [happy] GIVEN cache caliente WHEN se consultan anuncios THEN se sirve desde transient sin query a BD
  - [ ] [edge] GIVEN editor guarda/actualiza un anuncio WHEN save_post se dispara THEN se invalidan los transients de las zonas afectadas
  - [ ] [edge] GIVEN zona con anuncios caducados en cache WHEN se consultan THEN el filtro de fechas excluye los caducados aunque esten en cache
  - [ ] [restriccion] Rotacion usa setTimeout recursivo, no setInterval
  - [ ] [restriccion] WP_Query usa `no_found_rows => true` y `fields => 'ids'` para cachear solo IDs
  - [ ] [restriccion] Transicion fade dura 600ms con CSS opacity
  - [ ] [negativo] NO DEBE ejecutar WP_Query en cada page load si hay cache valido
  - [ ] [negativo] NO DEBE causar memory leaks en rotacion (limpiar timeout en visibilitychange/pagehide)
- **Archivos afectados**: ~4 archivos
  - Crear: `assets/js/geoad-rotation.js`
  - Crear: `includes/class-cache-manager.php`
  - Modificar: `includes/class-shortcode-geoad.php`
  - Modificar: `assets/css/geoad-frontend.css`
- **Sizing**: Archivos 2, Deps 2, Claridad alta, Riesgo medio — Score: 7/12
- **Dependencias**: T-004
- **Estado**: pendiente
- **Agentes**: tester → developer → reviewer

---

#### T-006 [feature] Caducidad automatica y filtrado por fechas
- **Descripcion**: Implementar logica de caducidad: los anuncios con `_geo_fecha_fin` anterior a hoy no se muestran en frontend. En admin, los anuncios caducados muestran estado visual "Caducado" en el listado. Registrar un wp_cron event diario que marque anuncios caducados como draft (opcional, la logica principal es el filtro en WP_Query). El estado se calcula dinamicamente comparando fecha_fin con la fecha actual, no se almacena como meta separado.
- **Criterios de aceptacion**:
  - [ ] [happy] GIVEN anuncio con fecha_fin = ayer WHEN la pagina se renderiza THEN el banner NO aparece en frontend
  - [ ] [happy] GIVEN anuncio con fecha_inicio = manana WHEN la pagina se renderiza THEN el banner NO aparece (aun no activo)
  - [ ] [happy] GIVEN anuncio con fecha_inicio <= hoy AND fecha_fin >= hoy WHEN la pagina se renderiza THEN el banner SI aparece
  - [ ] [edge] GIVEN anuncio sin fecha_fin definida WHEN se evalua THEN se considera activo indefinidamente
  - [ ] [edge] GIVEN anuncio sin fecha_inicio definida WHEN se evalua THEN se considera activo desde la publicacion
  - [ ] [error] GIVEN fecha_fin < fecha_inicio WHEN el editor guarda THEN se muestra admin_notice de advertencia
  - [ ] [restriccion] El filtro de fechas se aplica en la WP_Query del shortcode con meta_query
  - [ ] [restriccion] Cron event diario para marcar caducados como draft (geo_check_expired hook)
  - [ ] [negativo] NO DEBE eliminar anuncios caducados — solo cambiar a draft o filtrar en consulta
  - [ ] [negativo] NO DEBE depender exclusivamente del cron (el filtro en WP_Query es la fuente de verdad)
- **Archivos afectados**: ~3 archivos
  - Crear: `includes/class-cron-manager.php`
  - Modificar: `includes/class-shortcode-geoad.php`
  - Modificar: `includes/class-geogastronomica.php`
- **Sizing**: Archivos 2, Deps 2, Claridad alta, Riesgo medio — Score: 7/12
- **Dependencias**: T-004
- **Estado**: pendiente
- **Agentes**: tester → developer → reviewer

---

## Fase 3 — Features Avanzados (Admin, Estadisticas)

**Objetivo:** Mejorar la experiencia de administracion con columnas personalizadas, acciones en lote y estadisticas de impresiones/clicks. Al finalizar esta fase el editor tiene visibilidad completa del estado y rendimiento de sus anuncios.
**Estado:** pendiente

| ID | Etiqueta | Titulo | Estado | Dependencias |
|----|----------|--------|--------|--------------|
| T-007 | [feature] | Columnas admin personalizadas y acciones en lote | revision | T-003 |
| T-008 | [feature] | Estadisticas de impresiones y clicks | revision | T-005 |

---

#### T-007 [feature] Columnas admin personalizadas y acciones en lote
- **Descripcion**: Crear clase `Admin_Columns` que personalice el listado del CPT en wp-admin. Agregar columnas: Empresa (_geo_empresa_nombre), Descripcion (_geo_descripcion), Fecha Inicio, Fecha Fin, Estado (activo/caducado/programado calculado dinamicamente). Hacer las columnas ordenables por fecha. Agregar acciones en lote custom: "Activar" (publicar) y "Desactivar" (pasar a draft) ademas de las acciones nativas. Referencia: Ver `.planning/investigaciones/I-002-cpt-meta-boxes.md`
- **Criterios de aceptacion**:
  - [ ] [happy] GIVEN editor en listado de anuncios WHEN ve la tabla THEN ve columnas: Titulo, Empresa, Fecha Inicio, Fecha Fin, Estado
  - [ ] [happy] GIVEN anuncio con fecha_fin pasada WHEN se muestra en listado THEN la columna Estado muestra "Caducado" con estilo visual rojo
  - [ ] [happy] GIVEN editor selecciona 3 anuncios WHEN aplica accion "Desactivar" THEN los 3 pasan a estado draft
  - [ ] [edge] GIVEN anuncio sin empresa definida WHEN se muestra en listado THEN la columna Empresa muestra "—" (guion)
  - [ ] [edge] GIVEN listado con 50+ anuncios WHEN editor ordena por Fecha Fin THEN se ordena correctamente por meta value
  - [ ] [restriccion] Las columnas se registran con manage_geo_anuncio_posts_columns y se rellenan con manage_geo_anuncio_posts_custom_column
  - [ ] [negativo] NO DEBE sobreescribir las columnas nativas de titulo, fecha y checkbox
  - [ ] [negativo] NO DEBE ejecutar queries adicionales por cada fila — usar un pre-fetch en pre_get_posts si es necesario
- **Archivos afectados**: ~2 archivos
  - Crear: `includes/class-admin-columns.php`
  - Modificar: `includes/class-geogastronomica.php`
- **Sizing**: Archivos 1, Deps 2, Claridad alta, Riesgo bajo — Score: 5/12
- **Dependencias**: T-003
- **Estado**: pendiente
- **Agentes**: tester → developer → reviewer

---

#### T-008 [feature] Estadisticas de impresiones y clicks
- **Descripcion**: Implementar tracking de impresiones y clicks. Crear tabla custom `{prefix}geoad_stats` con columnas: id, post_id, event_type (impression|click), event_date, count. En frontend, inyectar data-attributes en los banners para tracking. Usar Beacon API (con fallback fetch) para enviar eventos a un REST endpoint privado. El endpoint registra el evento en la tabla custom. Cron diario para agregar datos raw y purgar registros > 30 dias. Mostrar stats basicos en el meta box del anuncio (impresiones totales, clicks totales, CTR). GDPR-friendly: sin cookies, sin IP. Referencia: Ver `.planning/investigaciones/I-009-estadisticas-clicks.md`
- **Criterios de aceptacion**:
  - [ ] [happy] GIVEN banner visible en viewport WHEN se renderiza THEN se envia evento impression via Beacon API
  - [ ] [happy] GIVEN usuario hace click en banner WHEN se registra click THEN el contador de clicks incrementa en la tabla geoad_stats
  - [ ] [happy] GIVEN editor editando anuncio WHEN accede al meta box de stats THEN ve impresiones totales, clicks y CTR
  - [ ] [edge] GIVEN navegador sin soporte Beacon API WHEN se renderiza banner THEN usa fallback con fetch()
  - [ ] [edge] GIVEN anuncio sin estadisticas WHEN editor ve el meta box THEN muestra "Sin datos aun"
  - [ ] [error] GIVEN REST endpoint recibe post_id invalido WHEN procesa evento THEN retorna 400 sin registrar
  - [ ] [restriccion] REST endpoint usa permission_callback con nonce publico (wp_create_nonce en frontend)
  - [ ] [restriccion] Cron diario agrega registros por dia y purga raw > 30 dias
  - [ ] [restriccion] Sin cookies, sin almacenamiento de IP — GDPR compliant
  - [ ] [negativo] NO DEBE usar wp_postmeta para escrituras frecuentes de stats (tabla custom obligatoria)
  - [ ] [negativo] NO DEBE impactar el tiempo de carga de la pagina (Beacon es asincrono)
- **Archivos afectados**: ~5 archivos
  - Crear: `includes/class-stats-tracker.php`
  - Crear: `includes/class-rest-stats.php`
  - Crear: `assets/js/geoad-tracking.js`
  - Modificar: `includes/class-shortcode-geoad.php`
  - Modificar: `includes/class-geogastronomica.php`
- **Sizing**: Archivos 3, Deps 3, Claridad media, Riesgo medio — Score: 9/12
- **Patron de splitting aplicado**: `major-effort` — Se evaluo dividir en (a) tabla+REST endpoint y (b) JS tracking + meta box stats. Sin embargo, al ser un vertical slice que entrega valor end-to-end (desde el click del usuario hasta la visualizacion de stats por el editor), dividirlo romperia la verificabilidad. Se mantiene como tarea unica con PLAN.md nivel 3.
- **Dependencias**: T-005
- **Estado**: pendiente
- **Agentes**: researcher → tester → developer → reviewer

---

## Fase 4 — Seguridad, i18n y Cierre

**Objetivo:** Hardening de seguridad, internacionalizacion, sistema de updates, uninstall limpio. Al finalizar esta fase el plugin esta listo para distribucion.
**Estado:** pendiente

| ID | Etiqueta | Titulo | Estado | Dependencias |
|----|----------|--------|--------|--------------|
| T-009 | [seguridad] | Hardening de seguridad en 4 capas | pendiente | T-003, T-008 |
| T-010 | [feature] | Internacionalizacion i18n completa | pendiente | T-003 |
| T-011 | [feature] | Sistema de actualizaciones desde GitHub | pendiente | T-001 |
| T-012 | [feature] | Uninstall limpio y desactivacion segura | pendiente | T-008 |

---

#### T-009 [seguridad] Hardening de seguridad en 4 capas
- **Descripcion**: Auditar y reforzar la seguridad del plugin aplicando las 4 capas obligatorias en orden: (1) Capabilities — verificar current_user_can() antes de cada operacion; (2) Nonces — wp_nonce_field/wp_verify_nonce en todos los formularios; (3) Sanitizacion — sanitize_*() en todos los inputs; (4) Escapado — esc_*() en todos los outputs. Crear helper `geo_verify_request($nonce_action, $capability)` que agrupe checks 1+2. Revisar todos los archivos existentes y corregir cualquier fallo. Referencia: Ver `.planning/investigaciones/I-005-seguridad-plugins-wp.md`
- **Criterios de aceptacion**:
  - [ ] [happy] GIVEN usuario sin capability edit_posts WHEN intenta guardar meta de anuncio THEN la operacion se rechaza silenciosamente
  - [ ] [happy] GIVEN formulario con nonce valido WHEN el editor guarda THEN los datos se procesan correctamente
  - [ ] [error] GIVEN request sin nonce o nonce invalido WHEN se intenta guardar THEN wp_verify_nonce retorna false y se aborta
  - [ ] [error] GIVEN input con HTML malicioso en campo empresa WHEN se guarda THEN sanitize_text_field() lo limpia
  - [ ] [restriccion] Helper geo_verify_request() combina check de capability + nonce en una sola llamada
  - [ ] [restriccion] Todo output HTML usa esc_html(), esc_attr(), esc_url() o wp_kses() segun contexto
  - [ ] [restriccion] Los archivos PHP incluyen check `defined('ABSPATH') || exit` en la primera linea
  - [ ] [negativo] NO DEBE exponer mensajes de error detallados al usuario no autenticado
  - [ ] [negativo] NO DEBE usar $wpdb->query() con interpolacion directa — siempre $wpdb->prepare()
- **Archivos afectados**: ~4 archivos
  - Crear: `includes/helpers/security.php`
  - Modificar: `includes/class-meta-boxes.php`
  - Modificar: `includes/class-rest-stats.php`
  - Modificar: `includes/class-shortcode-geoad.php`
- **Sizing**: Archivos 2, Deps 3, Claridad alta, Riesgo alto — Score: 8/12
- **Dependencias**: T-003, T-008
- **Estado**: pendiente
- **Agentes**: tester → developer → reviewer

---

#### T-010 [feature] Internacionalizacion i18n completa
- **Descripcion**: Aplicar internacionalizacion a todas las cadenas visibles del plugin usando las funciones nativas de WordPress: `__()`, `_e()`, `esc_html__()`, `esc_attr__()`. Text Domain: `geogastronomica` definido en el header del plugin. Para WP 6.8+ no se necesita `load_plugin_textdomain()`. Generar archivo POT con wp-cli o herramienta equivalente. Referencia: Ver `.planning/investigaciones/I-011-internacionalizacion.md`
- **Criterios de aceptacion**:
  - [ ] [happy] GIVEN plugin instalado WHEN WP esta en espanol THEN todas las cadenas del admin se muestran en espanol
  - [ ] [happy] GIVEN archivo .pot generado WHEN un traductor lo abre THEN contiene todas las cadenas traducibles del plugin
  - [ ] [edge] GIVEN WP en idioma sin traduccion disponible WHEN se usa el plugin THEN muestra cadenas en espanol (idioma base)
  - [ ] [restriccion] Text Domain `geogastronomica` esta en el header del plugin principal
  - [ ] [restriccion] Todas las cadenas visibles usan `__()` o `esc_html__()` — ninguna cadena hardcoded
  - [ ] [restriccion] No se llama a `load_plugin_textdomain()` (WP 6.8+ lo gestiona automaticamente)
  - [ ] [negativo] NO DEBE usar `_e()` en contextos donde se necesita return (usar `__()` en su lugar)
  - [ ] [negativo] NO DEBE traducir slugs internos, meta keys ni identificadores tecnicos
- **Archivos afectados**: ~5 archivos
  - Crear: `languages/geogastronomica.pot`
  - Modificar: `includes/class-cpt-anuncio.php`
  - Modificar: `includes/class-meta-boxes.php`
  - Modificar: `includes/class-admin-columns.php`
  - Modificar: `geogastronomica.php`
- **Sizing**: Archivos 3, Deps 1, Claridad alta, Riesgo bajo — Score: 6/12
- **Dependencias**: T-003
- **Estado**: pendiente
- **Agentes**: developer → reviewer

---

#### T-011 [feature] Sistema de actualizaciones desde GitHub
- **Descripcion**: Integrar la libreria plugin-update-checker v5.6 (YahnisElsts) para permitir actualizaciones automaticas del plugin desde GitHub Releases. Configurar con 4 lineas en la clase bootstrap: apuntar al repositorio GitHub, definir rama principal, y activar soporte para releases con ZIP adjunto. El usuario vera las actualizaciones disponibles en la pantalla nativa de plugins de WordPress. Referencia: Ver `.planning/investigaciones/I-006-sistema-actualizaciones.md`
- **Criterios de aceptacion**:
  - [ ] [happy] GIVEN nueva release en GitHub WHEN WordPress chequea updates THEN muestra notificacion de actualizacion disponible
  - [ ] [happy] GIVEN actualizacion disponible WHEN el admin hace click en "Actualizar" THEN el plugin se actualiza desde el ZIP de la release
  - [ ] [edge] GIVEN repositorio GitHub inaccesible WHEN WordPress chequea updates THEN no produce error visible (fallo silencioso)
  - [ ] [edge] GIVEN plugin sin release en GitHub WHEN se activa THEN funciona normalmente sin errores
  - [ ] [restriccion] plugin-update-checker se instala via Composer require (no copiar archivos manualmente)
  - [ ] [restriccion] La configuracion son maximo 5 lineas en la clase bootstrap
  - [ ] [negativo] NO DEBE exponer tokens de GitHub en el codigo fuente
  - [ ] [negativo] NO DEBE bloquear la activacion del plugin si GitHub no responde
- **Archivos afectados**: ~2 archivos
  - Modificar: `composer.json`
  - Modificar: `includes/class-geogastronomica.php`
- **Sizing**: Archivos 1, Deps 2, Claridad alta, Riesgo bajo — Score: 5/12
- **Dependencias**: T-001
- **Estado**: pendiente
- **Agentes**: developer → reviewer

---

#### T-012 [feature] Uninstall limpio y desactivacion segura
- **Descripcion**: Implementar `uninstall.php` con check `WP_UNINSTALL_PLUGIN` que borre en orden: (1) todos los posts del CPT geo_anuncio y sus meta, (2) tabla custom geoad_stats, (3) transients del plugin, (4) opciones del plugin. Tambien implementar hook de desactivacion que limpie cron events programados. El borrado de datos debe ser completo — no dejar huella en la BD. Referencia: Ver `.planning/investigaciones/I-013-uninstall-migracion.md`
- **Criterios de aceptacion**:
  - [ ] [happy] GIVEN plugin desinstalado via wp-admin WHEN se verifica la BD THEN no existen posts de tipo geo_anuncio
  - [ ] [happy] GIVEN plugin desinstalado WHEN se verifica la BD THEN la tabla geoad_stats no existe
  - [ ] [happy] GIVEN plugin desactivado WHEN se verifican cron events THEN el hook geo_check_expired no esta programado
  - [ ] [edge] GIVEN plugin desinstalado sin anuncios creados WHEN se ejecuta uninstall THEN no produce error
  - [ ] [error] GIVEN uninstall.php ejecutado fuera de WordPress WHEN se accede directamente THEN exit inmediato (check WP_UNINSTALL_PLUGIN)
  - [ ] [restriccion] Orden de borrado: posts → tabla custom → transients → opciones
  - [ ] [restriccion] Usa $wpdb->prepare() para todas las queries de borrado
  - [ ] [negativo] NO DEBE borrar datos de otros plugins o del core de WordPress
  - [ ] [negativo] NO DEBE ejecutar borrado en desactivacion — solo en desinstalacion (uninstall.php)
- **Archivos afectados**: ~2 archivos
  - Modificar: `uninstall.php`
  - Modificar: `includes/class-geogastronomica.php` (hook desactivacion para limpiar cron)
- **Sizing**: Archivos 1, Deps 2, Claridad alta, Riesgo alto — Score: 7/12
- **Dependencias**: T-008
- **Estado**: pendiente
- **Agentes**: tester → developer → reviewer

---

## Dependencias entre tareas

```
T-001 (Scaffolding)
├── T-002 (CPT)
│   └── T-003 (Meta Boxes)
│       ├── T-004 (Shortcode + Responsive)
│       │   ├── T-005 (Rotacion + Cache)
│       │   │   └── T-008 (Estadisticas)
│       │   │       ├── T-009 (Seguridad) ← tambien depende de T-003
│       │   │       └── T-012 (Uninstall)
│       │   └── T-006 (Caducidad)
│       ├── T-007 (Admin Columns)
│       └── T-010 (i18n)
└── T-011 (Updates GitHub)
```

Tareas paralelizables dentro de cada fase:
- Fase 2: T-005 y T-006 pueden ejecutarse en paralelo (ambas dependen de T-004)
- Fase 3: T-007 y T-008 pueden ejecutarse en paralelo (dependen de T-003 y T-005 respectivamente)
- Fase 4: T-010 y T-011 pueden ejecutarse en paralelo (dependen de T-003 y T-001 respectivamente)

---

## Criterios de aceptacion globales

- [ ] El plugin se activa sin errores en WordPress 6.x+ con PHP 8.0+
- [ ] El plugin funciona sin ningun page builder instalado (Bricks, Elementor, etc.)
- [ ] Lighthouse Performance > 90 con banners activos
- [ ] CLS < 0.1 en paginas con banners
- [ ] Maximo 2 queries a BD por zona por pagina (con cache activo)
- [ ] El editor (usuario no tecnico) puede gestionar anuncios sin ayuda
- [ ] Los shortcodes renderizan correctamente en cualquier tema WordPress
- [ ] El plugin se desinstala sin dejar basura en la BD
- [ ] Todo el codigo sigue WordPress Coding Standards
- [ ] Todas las cadenas visibles estan internacionalizadas

---

## Resumen

| Fase | Total | Pendientes | En progreso | Completadas |
|------|-------|------------|-------------|-------------|
| Fase 1 — Setup y Fundamentos | 3 | 3 | 0 | 0 |
| Fase 2 — Core Frontend | 3 | 3 | 0 | 0 |
| Fase 3 — Features Avanzados | 2 | 2 | 0 | 0 |
| Fase 4 — Seguridad, i18n y Cierre | 4 | 4 | 0 | 0 |
| **Total** | **12** | **12** | **0** | **0** |
