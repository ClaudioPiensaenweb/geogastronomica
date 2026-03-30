# Briefing — GeoGastronomica Plugin

## Qué es

Plugin WordPress propietario para gestionar banners publicitarios propios en geogastronomica.com.
Sustituye Google AdSense con banners de clientes directos, sin depender de plataformas externas.

## Por qué existe

- El sitio geogastronomica.com quiere monetizar con publicidad propia (sin intermediarios)
- Necesitan control total sobre los anuncios: qué se muestra, dónde, cuándo y a quién
- Quieren ofrecer a anunciantes locales (restaurantes, turismo) packs de visibilidad con zonas concretas
- Las estadísticas propias (impresiones/clicks) permiten justificar el precio al anunciante

## Qué hace el plugin

1. **CPT `geo_anuncio`** — cada anuncio es un post con 3 tabs:
   - Info empresa (nombre, email, teléfono)
   - Anuncio (imágenes por formato, enlace, descripción)
   - Configuración (fechas, zonas, impresiones contratadas)

2. **Shortcode `[geoad zone="..."]`** — renderiza la zona en el frontend:
   - Detecta el formato según la zona (vertical/horizontal)
   - Soporta múltiples anuncios con rotación automática
   - Lazy loading de imágenes y vídeos
   - Sticky banner en móvil

3. **Ajustes** — página de configuración con:
   - Packs de visibilidad (zonas agrupadas con precio)
   - Inserción automática en artículos (después del párrafo N)
   - Intervalo de rotación configurable
   - URL de política de privacidad para el badge "Publicidad"

4. **Estadísticas** — tabla custom `wp_geoad_stats`:
   - Registro de impresiones y clicks (server-side, sin cookies)
   - Meta box en cada anuncio con barra de progreso vs impresiones contratadas
   - Ritmo diario (últimos 7 días) y fecha estimada de cumplimiento

5. **Cumplimiento legal** (España):
   - Badge "Publicidad" en cada banner (LSSI Art. 20)
   - Sin cookies ni tracking del lado del cliente (RGPD simplificado)
   - URL de privacidad configurable que enlaza el badge

## Usuarios del plugin

- **Admin del sitio**: crea y gestiona anuncios desde el panel de WordPress
- **Visitantes del sitio**: ven los banners en el frontend
- **Anunciantes**: reciben informes de impresiones/clicks (se los enseña el admin)

## Restricciones conocidas

- Solo funciona con Bricks Builder (hay CSS específico para `.brxe-shortcode`)
- El tracking es diario agregado — no hay datos individuales de visitantes
- Los vídeos usan lazy loading manual (IntersectionObserver) por rendimiento
