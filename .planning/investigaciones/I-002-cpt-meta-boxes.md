# Investigacion: Custom Post Type + Meta Boxes con tabs

> Proyecto: GeoGastronomica | Area: stack | Prioridad: alta

## Resumen ejecutivo

La API nativa de WordPress (`register_post_type` + `add_meta_box`) es suficiente para este plugin sin dependencias externas. Los tabs se implementan con clases CSS nativas de WordPress (`categorydiv`, `category-tabs`, `tabs-panel`) y el JS de core los activa automaticamente — sin una sola linea de JS propio. Esta es la opcion recomendada para un plugin que debe ser autocontenido, ligero y sin dependencias externas.

## Hallazgos clave

1. **WordPress nativo soporta tabs sin JS custom.** Usando las clases `categorydiv > .category-tabs > .tabs-panel`, el core de WordPress activa el comportamiento de tabs automaticamente. No se necesita escribir JavaScript.

2. **CMB2 se puede bundlear de forma segura**, pero agrega ~150KB al plugin y sus tabs (cmb2-tabs) llevan sin actualizar desde 2017 — descartado para tabs.

3. **ACF Pro requiere licencia de pago** y crea dependencia externa. ACF Free (post-adquisicion por WP Engine) tiene funcionalidad limitada. Descartado para un plugin distribuible.

4. **Carbon Fields es gratuito y orientado a codigo**, pero sin soporte nativo de tabs y con menor actividad en el repositorio (ultima release significativa 2023). Descartado.

5. **WordPress 7.0 trae el editor en iframe** — los meta boxes clasicos seguiran funcionando via `__block_editor_compatible_meta_box`, pero conviene registrar los campos tambien con `register_post_meta` + `show_in_rest: true` para compatibilidad futura con el editor de bloques.

6. **El patron nativo de tabs requiere estructura HTML especifica:** wrapper con clase `categorydiv`, lista `category-tabs`, paneles `tabs-panel`. Es ~30 lineas de HTML en el callback del meta box — manejable sin librerias.

## Recomendacion

Usar **API nativa de WordPress** para todo: `register_post_type`, `add_meta_box`, `get_post_meta` / `update_post_meta`. Implementar los 3 tabs (Info empresa, Anuncio, Configuracion) con las clases nativas de WordPress. Registrar todos los meta con `register_post_meta` + sanitize callbacks para compatibilidad REST y seguridad.

Estructura del meta box unico con tabs:

```
add_meta_box('geoad_details', 'Datos del anuncio', 'geoad_render_meta_box', 'geoad_banner', 'normal', 'high')
```

El callback renderiza el HTML con las 3 tabs usando `categorydiv` + nonce de seguridad.

## Alternativas consideradas

| Opcion | Pros | Contras |
|--------|------|---------|
| API nativa WP | Sin dependencias, ligero, futuro-proof, tabs gratis via CSS core | Mas codigo PHP manual (~100 lineas extra vs CMB2) |
| CMB2 (bundled) | DX rapido, bundleable, extensible | +150KB, tabs addon abandonado (2017), sobre-ingenierizacion |
| Carbon Fields | Gratuito, orientado a codigo, Composer-friendly | Sin tabs nativo, menor actividad 2023-2025, dependencia externa |
| ACF Pro | UX excelente, muy popular | Licencia de pago, dependencia externa, no distribuible con el plugin |
| Meta Box + MB Tabs | Tabs nativos elegantes, bien mantenido | Freemium: MB Tabs requiere licencia (29$/año), dependencia externa |

## Referencias

- [add_meta_box() — WordPress Developer](https://developer.wordpress.org/reference/functions/add_meta_box/)
- [register_post_type() — WordPress Developer](https://developer.wordpress.org/reference/functions/register_post_type/)
- [Create Tabs in Meta Boxes (native CSS classes) — Rudrastyh](https://rudrastyh.com/wordpress/create-tabs-in-meta-boxes.html)
- [CMB2 GitHub — CMB2/CMB2](https://github.com/CMB2/CMB2)
- [Carbon Fields — carbonfields.net](https://carbonfields.net/about/)
- [WordPress 7.0 iframed editor migration — DEV Community](https://dev.to/victorstackai/wordpress-70-iframed-editor-migration-playbook-for-meta-boxes-plugins-and-admin-js-55pm)
- [Meta Box vs ACF — metabox.io](https://metabox.io/meta-box-vs-acf/)
