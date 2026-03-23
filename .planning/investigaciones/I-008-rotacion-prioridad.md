# Investigacion: Algoritmo de rotacion y prioridad de anuncios

> Proyecto: GeoGastronomica | Area: patrones | Prioridad: media

## Resumen ejecutivo

Para un plugin WordPress con pocos anuncios por zona (tipicamente 2-5), el algoritmo optimo es **priority-first con weighted random como desempate**: se ordenan los anuncios por prioridad numerica y, si hay empate, se elige aleatoriamente con peso. La rotacion frontend se implementa con JS vanilla usando CSS `opacity` + `transition`, sin setInterval continuo — usando `setTimeout` recursivo para evitar deriva de tiempo. La cache con Transient API almacena **array de IDs** (no el objeto WP_Query) con invalidacion en `save_post`.

---

## Hallazgos clave

1. **Priority-first es suficiente para este caso de uso.** Weighted random puro (lottery scheduling) es ideal cuando todos los anuncios tienen igual valor. Aqui hay prioridad explicita del editor, por lo que el algoritmo correcto es: ordenar por `_geo_prioridad DESC`, romper empates con `rand()`. Round-robin requiere estado persistente (contador por zona en BD), innecesario para el volumen esperado.

2. **Transient API: cachear IDs, no WP_Query.** Cachear el objeto WP_Query completo provoca un efecto inverso: al reconstruirlo desde transient, WordPress no tiene el objeto cache calentado y dispara multiples queries pequenas para meta y taxonomias. El patron correcto es `get_transient('geoad_zona_home_vertical') → array de post IDs → get_post_meta()` por ID. TTL recomendado: 1 hora (3600s). Invalidar con hook `save_post_{post_type}`.

3. **WP_Query con meta_query para fechas activas.** Filtrar anuncios activos requiere dos condiciones combinadas con `relation => AND`: `_geo_fecha_inicio <= fecha_hoy` y `(_geo_fecha_fin >= fecha_hoy OR _geo_fecha_fin no existe)`. Usar `meta_type => DATE` y formato `Y-m-d` para comparacion correcta. El campo `post_status => publish` filtra los desactivados manualmente.

4. **Rotacion JS: setTimeout recursivo + CSS transition.** `setInterval` acumula deriva temporal y no respeta la duracion real de la transicion. El patron correcto: fade-out (opacity 0, esperar `transitionend`), cambiar imagen, fade-in (opacity 1). Si solo hay un anuncio, no inicializar el rotador. Escuchar `visibilitychange` para pausar cuando la pestana esta oculta (ahorra CPU y evita contar impresiones falsas).

5. **Maximo 2 queries por pagina es alcanzable.** Query 1: obtener IDs activos por zona (cacheada en transient). Query 2: `get_post_meta()` en batch para los IDs obtenidos. Con Transient activo la primera query desaparece en cargas subsecuentes dentro del TTL.

---

## Recomendacion

Implementar el siguiente flujo en PHP:

```
get_transient(key) → miss → WP_Query(meta_query fechas, orderby prioridad DESC)
→ extraer IDs → set_transient(IDs, 3600)
→ get_post_meta batch sobre IDs → construir array de anuncios
→ renderizar HTML con todos los anuncios apilados (position: absolute, opacity: 0)
→ JS activa el primero (opacity: 1) y rota con setTimeout + transitionend
```

Para el algoritmo de seleccion del primer anuncio a mostrar (y orden de rotacion): ordenar por `_geo_prioridad DESC` en la query, sin logica adicional en PHP. El anuncio de mayor prioridad siempre aparece primero; la rotacion JS recorre el array en orden.

---

## Alternativas consideradas

| Opcion | Pros | Contras |
|--------|------|---------|
| Round-robin con contador en BD | Distribucion exacta y equitativa | Requiere tabla o option por zona, escrituras en BD en cada pageview, complejo |
| Weighted random puro (lottery) | Justo estadisticamente si todos valen igual | Ignora la prioridad explicita del editor, no deterministico |
| Rotacion solo en frontend (JS fetch) | Cero impacto en TTFB | Requiere endpoint REST, retraso visible, peor SEO/CLS |
| setInterval para rotacion | Simple de implementar | Deriva temporal, no respeta duracion de transicion, consume CPU en pestanas ocultas |
| Cachear objeto WP_Query completo | Una sola llamada a get_transient | Provoca N queries pequeñas al reconstruir — peor que sin cache |

---

## Referencias

- [Transients API — WordPress Developer Handbook](https://developer.wordpress.org/apis/transients/)
- [An introduction to the Transients API — WordPress Developer Blog](https://developer.wordpress.org/news/2024/06/an-introduction-to-the-transients-api/)
- [Cache WordPress Query Strings Using Transient API — Paulund](https://paulund.co.uk/cache-wordpress-query-strings-using-transient-api)
- [Using CSS transitions — MDN](https://developer.mozilla.org/en-US/docs/Web/CSS/Guides/Transitions/Using)
- [Advanced Ads: weighted rotation reference](https://wpadvancedads.com/manual/rotate-ad/)
