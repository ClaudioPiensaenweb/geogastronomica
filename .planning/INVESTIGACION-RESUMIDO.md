# Investigacion Resumida — GeoGastronomica

> Fecha: 2026-03-23
> Temas investigados: 15/15

## Stack recomendado

| Componente | Tecnologia | Justificacion |
|------------|-----------|---------------|
| Lenguaje | PHP 8.0+ | WordPress nativo |
| Arquitectura | OOP con PSR-4 (Composer classmap) | Clases con responsabilidad unica, sin boilerplate externo |
| CPT + Meta | API nativa WordPress | register_post_type + add_meta_box con tabs custom (sin CMB2/ACF) |
| Frontend | Shortcodes PHP + JS vanilla | Desacoplado de page builders |
| Responsive | `<picture>` + `<source media>` | Solo descarga imagen del breakpoint activo |
| Cache | Transient API | Cache de IDs por zona, invalidacion en save_post |
| Testing | PHPUnit + Brain Monkey + @wordpress/env | Unitarios sin WP + integracion con WP |
| Updates | plugin-update-checker (YahnisElsts) + GitHub Releases | Auto-update desde GitHub sin servidor propio |
| i18n | gettext nativo WP 6.8+ | Text Domain en header, sin load_plugin_textdomain() |
| Seguridad | 4 capas: capabilities, nonces, sanitizacion, escapado | Siguiendo OWASP para WordPress |
| Stats | Tabla custom + Beacon API + REST endpoint | Sin cookies/IP, GDPR-friendly |

## Tabla de hallazgos

| Tema | Hallazgo clave | Recomendacion | Archivo |
|------|---------------|---------------|---------|
| I-001 Arquitectura | OOP propio > WPPB (sin mantenimiento desde 2022) | 6-7 clases con responsabilidad unica, Singleton solo para bootstrap | I-001-arquitectura-plugin-wp.md |
| I-002 CPT + Meta Boxes | API nativa suficiente, CMB2/ACF innecesario | Meta boxes con tabs via JS vanilla, campos con prefijo _geo_ | I-002-cpt-meta-boxes.md |
| I-003 Shortcodes | Return nunca echo, enqueue condicional dentro del callback | Cache con transients obligatorio para cumplir <= 2 queries/pagina | I-003-shortcodes-frontend.md |
| I-004 WP Agent Skills | Solo 2 skills relevantes: wp-plugin-development + wp-performance | Instalar antes de desarrollar, no las 10 restantes | I-004-wp-agent-skills.md |
| I-005 Seguridad | 4 capas obligatorias en orden: capabilities > nonces > sanitizacion > escapado | Helper geo_verify_request() para agrupar checks | I-005-seguridad-plugins-wp.md |
| I-006 Updates | plugin-update-checker v5.6 (MIT, activo, 4 lineas de config) | GitHub Releases con ZIP adjunto para distribuir updates | I-006-sistema-actualizaciones.md |
| I-007 Responsive | `<picture>` evita descarga de imagenes no usadas (CSS display:none no) | loading="lazy" nativo + aspect-ratio para CLS < 0.1 | I-007-responsive-banners.md |
| I-008 Rotacion | Priority-first en WP_Query, setTimeout recursivo (no setInterval) | Cachear array de IDs en transient, no objetos WP_Query completos | I-008-rotacion-prioridad.md |
| I-009 Stats | Beacon API + REST endpoint + tabla custom geoad_stats | Cron diario para agregar y purgar raw > 30 dias, GDPR sin cookies | I-009-estadisticas-clicks.md |
| I-010 Testing | PHPUnit + Brain Monkey para unitarios, @wordpress/env para integracion | wp scaffold plugin-tests para setup inicial | I-010-testing-plugins-wp.md |
| I-011 i18n | WP 6.8 elimina load_plugin_textdomain() | Aplicar desde el inicio (coste ~0), esc_html__() combina escape+traduccion | I-011-internacionalizacion.md |
| I-012 Gutenberg | No implementar en MVP, shortcodes cubren todos los casos | Si se hace despues: block.json + render.php reutilizando logica del shortcode | I-012-bloque-gutenberg.md |
| I-013 Uninstall | uninstall.php con check WP_UNINSTALL_PLUGIN, borrado en orden | Migracion JetEngine: tabla wp_jet_cct_{slug}, mapeo directo de columnas | I-013-uninstall-migracion.md |
| I-014 Performance | Transient API sin Redis, no_found_rows => true en WP_Query | Compatible con WP Rocket/LiteSpeed sin config extra, rotacion en cliente JS | I-014-performance-cache.md |
| I-015 Plugins similares | Nicho "banners propios para editor no tecnico" esta libre | Diferenciadores: responsive 4 formatos, lazy loading, caducidad, UX simple | I-015-plugins-similares.md |

## Alertas y decisiones arquitectonicas

- **PSR-4 vs WPCS**: Tension entre nombres de archivo PSR-4 (NombreClase.php) y WordPress Coding Standards (class-nombre-clase.php). Solucion: usar Composer classmap.
- **Bloque Gutenberg**: Descartado para MVP. Requiere build step con Node.js que rompe la premisa de PHP nativo. Reservado como "Could have" futuro.
- **Stats tabla custom vs post meta**: Tabla custom obligatoria. wp_postmeta se degrada con escrituras frecuentes de tracking.
- **Beacon API**: Requiere fallback a fetch() para navegadores antiguos (< 1% del trafico).

## Dependencias entre temas

- I-003 (Shortcodes) → I-008 (Rotacion): el shortcode renderiza el HTML, la rotacion JS lo anima
- I-003 (Shortcodes) → I-007 (Responsive): el shortcode genera el `<picture>` con sources
- I-003 (Shortcodes) → I-014 (Cache): transients dentro del callback del shortcode
- I-005 (Seguridad) → I-002 (CPT): sanitizacion/escapado en todos los meta fields
- I-009 (Stats) → I-003 (Shortcodes): el shortcode inyecta data-attributes para tracking JS
- I-006 (Updates) → I-001 (Arquitectura): plugin-update-checker se integra en la clase bootstrap

## Investigaciones fallidas

Ninguna. 15/15 completadas exitosamente.

## Siguiente paso

Ejecuta `/piensa:planificar` para generar tareas incorporando estos hallazgos.
