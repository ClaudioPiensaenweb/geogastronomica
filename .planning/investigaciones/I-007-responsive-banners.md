# Investigacion: Responsive images y formatos de banner

> Proyecto: GeoGastronomica | Area: performance | Prioridad: media

## Resumen ejecutivo

Para los 4 formatos de banner (vertical 285x627, cuadrado 285x285, horizontal 1230x350, movil 1000x400), la solucion correcta es `<picture>` con `<source media="">` — no CSS `display:none`. El navegador descarga todas las imagenes antes de aplicar CSS, lo que hace que el enfoque CSS sea un desperdicio de ancho de banda. El CLS se previene con `aspect-ratio` en el contenedor del banner, no con dimensiones fijas. El lazy loading nativo (`loading="lazy"`) es suficiente para banners fuera del viewport superior; los banners above-the-fold NO deben llevar lazy loading.

## Hallazgos clave

1. **`display:none` descarga igualmente la imagen.** El parser HTML solicita recursos antes de que CSS se aplique. Usar CSS para ocultar formatos de banner implica que el movil descarga tambien el banner horizontal de 1230x350. Coste real en datos moviles.

2. **`<picture>` con `<source media>` es la unica forma correcta.** El navegador evalua los `<source>` en orden y solo descarga la imagen del primer `<source>` que coincide. Para este plugin, el orden debe ser: movil primero (max-width), luego vertical/cuadrado segun zona, luego horizontal para desktop.

3. **`loading="lazy"` nativo cubre el caso de uso del plugin.** Los banners en home/categoria/articulo normalmente estan below-the-fold. El atributo nativo tiene soporte universal (96%+ navegadores 2025) y no requiere JS adicional. Intersection Observer solo aporta valor si se necesita control preciso del threshold o lazy loading de fondo CSS — no es el caso aqui. EXCEPCION: si un banner esta en la cabecera de la pagina (above-the-fold), omitir `loading="lazy"` para no penalizar LCP.

4. **CLS: reservar espacio con `aspect-ratio` en el contenedor.** La tecnica moderna es CSS `aspect-ratio` en el wrapper del banner, combinado con `width:100%; height:auto` en la imagen. Alternativamente, los atributos `width` y `height` en el `<img>` permiten al navegador calcular el aspect-ratio automaticamente antes de la carga. Ambas tecnicas alcanzan CLS < 0.1 — objetivo del briefing.

5. **WebP + fallback JPEG es el minimo viable; AVIF es mejora opcional.** WebP tiene 95% de soporte en 2025. AVIF tiene 93.8% pero mejor compresion. WordPress 5.8+ genera WebP automaticamente desde la Media Library. El plugin puede delegar la gestion de formatos en WordPress/servidor y simplemente usar la URL de la imagen tal como la devuelve la Media Library, que ya sera WebP si el servidor lo soporta.

## Recomendacion

Usar `<picture>` con `<source media>` para seleccion de formato segun viewport. Anadir `loading="lazy"` a la imagen fallback excepto en banners above-the-fold. Reservar espacio con `aspect-ratio` en el contenedor CSS del plugin. No implementar Intersection Observer — el atributo nativo es suficiente y elimina dependencia JS. No gestionar WebP/AVIF manualmente: confiar en la Media Library de WordPress que ya optimiza los formatos.

Estructura HTML recomendada para el shortcode:

```html
<div class="geoad-banner" style="aspect-ratio: 285/627;">
  <picture>
    <source media="(max-width: 768px)"  srcset="{url_movil}">
    <source media="(min-width: 1024px)" srcset="{url_horizontal}">
    <img src="{url_vertical_o_cuadrado}" alt="{descripcion}" loading="lazy" width="285" height="627">
  </picture>
</div>
```

El `aspect-ratio` del contenedor debe ser dinamico segun el formato que se muestra en cada breakpoint — esto se resuelve con clases CSS por zona en el plugin.

## Alternativas consideradas

| Opcion | Pros | Contras |
|--------|------|---------|
| `<picture>` + `<source media>` | Cero JS, solo descarga la imagen necesaria, soporte universal | El PHP del plugin debe generar el HTML con las 4 URLs |
| CSS `display:none` por breakpoint | Simple de implementar | Descarga todas las imagenes siempre, penaliza movil |
| Intersection Observer JS | Control preciso del threshold | Requiere JS adicional, innecesario para este caso |
| `srcset` + `sizes` en `<img>` | Bueno para densidad de pantalla (retina) | No sirve para cambiar la composicion del banner (diferente recorte) — solo cambia resolucion |

## Referencias

- [MDN: Responsive images](https://developer.mozilla.org/en-US/docs/Web/HTML/Guides/Responsive_images)
- [MDN: picture element](https://developer.mozilla.org/en-US/docs/Web/HTML/Element/picture)
- [web.dev: Optimize CLS](https://web.dev/articles/optimize-cls)
- [swimburger.net: display:none descarga imagenes igualmente](https://swimburger.net/blog/web/web-performance-prevent-wasteful-hidden-image-requests)
- [VitalsFixer: Lazy Loading nativo 2026](https://vitalsfixer.com/blog/lazy-loading-guide)
- [SpeedVitals: WebP vs AVIF 2025](https://speedvitals.com/blog/webp-vs-avif/)
