# Roadmap — GeoGastronomica Plugin

## Fase 1 — Core funcional ✅ COMPLETADA

- ✓ CPT `geo_anuncio` con meta boxes (empresa, anuncio, config)
- ✓ Shortcode `[geoad zone="..."]` con picture/source responsive
- ✓ Rotación automática de banners (JS vanilla)
- ✓ Soporte vídeo con lazy loading (IntersectionObserver)
- ✓ Sticky banner en móvil
- ✓ Sistema de packs de visibilidad
- ✓ Inserción automática en artículos
- ✓ Plugin Update Checker desde GitHub Releases
- ✓ GitHub Action para build + release automático
- ✓ Tabla `wp_geoad_stats` con impresiones y clicks

## Fase 2 — Estabilización y polish ✅ COMPLETADA (v1.8–v1.9.9)

- ✓ Fix `<br>`/`<p>` dentro del banner (wpautop en ob_start)
- ✓ Fix ZIP con separadores de ruta en Windows (build-zip.py)
- ✓ Fix `isolation: isolate` solapando subheader sticky
- ✓ Fix `margin: 40px 0` del tema en imágenes del banner
- ✓ Intervalo de rotación configurable desde Ajustes
- ✓ Badge "Publicidad" legal (LSSI Art. 20) — absoluto dentro del banner
- ✓ URL de política de privacidad configurable (RGPD)
- ✓ Vista previa de formatos en meta box del anuncio
- ✓ Campo "Impresiones contratadas" en tab Configuración
- ✓ Meta box de estadísticas con barra de progreso, ritmo diario y fecha estimada
- ✓ README con flujo de desarrollo documentado

## Fase 3 — Mejoras pendientes

- ✓ Inserción automática no pegada a imágenes — detecta img/figure (Gutenberg y clásico) y desliza hasta 4 párrafos
- ✓ Badge "Publicidad" sincronizado con rotación — se muestra/oculta según el banner activo (data-mostrar-publicidad)
- ▸ Página de comparativa de anuncios — ranking por CTR/impresiones de todos los activos
- ▸ Exportar estadísticas a CSV (para enviar al anunciante)
- ▸ Notificación cuando un anuncio cumple el objetivo de impresiones
- ▸ Soporte para formato cuadrado (redes sociales / sidebar)
- ▸ Preview del banner en el shortcode antes de publicar (admin-only overlay)
- ▸ Caducidad automática: desactivar anuncio al llegar a las impresiones contratadas

## Fase 4 — Posibles futuras (sin priorizar)

- ○ Dashboard global con métricas de todos los anuncios activos
- ○ Histórico de estadísticas más allá de 30 días (retención configurable)
- ○ API REST para consultar estadísticas desde fuera de WordPress
- ○ Soporte multisite
