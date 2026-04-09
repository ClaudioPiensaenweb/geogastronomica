# Estado del Proyecto — GeoGastronomica Plugin

> Última actualización: 2026-04-09 por alexPiensaenweb
> Handoff preparado: sí

---

## Fase actual

**Fase 3 en progreso** — Mejoras y estabilización de producción

Versión actual: **v2.0.6**

## Progreso

`█████████████████████░░░` ~87% del producto mínimo viable

- **Completadas**: Fase 1 completa, Fase 2 completa, 2 tareas de Fase 3, + 6 hotfixes de producción (v2.0.1→v2.0.6)
- **En progreso**: ninguna
- **Pendientes**: 6 tareas de Fase 3 + Fase 4 completa

## Qué se hizo hoy (2026-04-09)

### Hotfixes de producción (reportados por el cliente)

- ✓ **v2.0.2** — Bug crítico: anuncios sin fecha fin se desactivaban solos cada noche.
  Causa: el cron comparaba `_geo_fecha_fin` vacío con `< hoy` en MySQL y `'' < '2026-04-09'` = true.
  Fix: meta_query ahora requiere `_geo_fecha_fin != ''` antes del comparador de fecha.

- ✓ **v2.0.3** — Tres fixes:
  - CSS: badge "Publicidad" no se ocultaba aunque estuviera desactivado.
    Causa: especificidad `.geoad-zone .geoad-label` (0,2,0) ganaba sobre `.geoad-label--hidden` (0,1,0) con `!important`.
    Fix: `.geoad-zone .geoad-label.geoad-label--hidden` (0,3,0).
  - Admin: campo de video en meta box aparecía vacío al reabrir el anuncio.
    Causa: `wp_get_attachment_image_url()` devuelve `false` para videos.
    Fix: `get_post_mime_type()` + icono dashicon en las tarjetas de imagen.
  - Auto-inject: banners se inyectaban dentro de blockquotes (`<blockquote>`).
    Causa: `explode('</p>', $content)` parte el `</p>` interior del blockquote.
    Fix: `is_adjacent_to_image()` detecta `<blockquote` en parte actual y `</blockquote` en siguiente.

- ✓ **v2.0.4** — Dos fixes:
  - Vista previa de video en sección "Formatos" del admin. Ahora renderiza `<video>` real con controles.
  - Badge "Publicidad" ahora es **oculto por defecto** — solo se muestra si
    `_geo_mostrar_publicidad = '1'` explícitamente. Antes, vacío = mostrar (causaba
    que todos los anuncios mostraran el badge hasta ser editados manualmente).

- ✓ **v2.0.5** — Guardia de salida en auto-inject:
  - Añadida comprobación final en el bucle de ensamblado de `inject()`.
  - Si el segmento siguiente empieza con etiqueta de cierre de bloque (`</blockquote>`,
    `</td>`, `</li>`, `</th>`, `</dt>`, `</dd>`, `</caption>`, `</cite>`, `</figure>`),
    no se inyecta aunque el mapa diga que sí.
  - Belt-and-suspenders sobre la lógica de pre-cálculo de v2.0.3.

- ✓ **v2.0.6** — Fix sliding demasiado agresivo en auto-inject:
  - `is_adjacent_to_image()` bloqueaba cuando `$parts[$index]` empezaba con `<figure>`.
  - Ese figura estaba ANTES del párrafo actual (entre el anterior y el actual),
    no entre el párrafo actual y el punto de inyección.
  - Fix: eliminado `<figure[\s>]` del primer check. Solo `<blockquote` bloquea en
    `$parts[$index]`. Las imágenes que bloquean deben estar en `$parts[$index+1]`.

## Siguiente tarea recomendada

**Comparativa de anuncios** — página en el admin que muestra todos los anuncios activos
ordenados por CTR e impresiones. Los datos ya están en `wp_geoad_stats`.

Otras pendientes en Fase 3:
- Exportar estadísticas a CSV
- Notificación cuando un anuncio cumple el objetivo de impresiones
- Caducidad automática al llegar a impresiones contratadas
- Soporte para formato cuadrado (redes sociales / sidebar)
- Preview del banner en el shortcode (admin-only overlay)
- ⚠️ Bug latente: `aggregate_and_purge()` borra datos >30 días, lo que hace que
  "Total acumulado" en el meta box de estadísticas sea engañoso para campañas largas.
  Fix pendiente: acumular en post_meta antes de purgar.

## Notas del dev saliente

> Autor: alexPiensaenweb
> Fecha: 2026-04-09

Sin notas adicionales. Toda la información está en los documentos de .planning/.

---

## Bugs encontrados y corregidos en producción (patrones importantes)

1. **Comparaciones de fecha en MySQL con strings vacíos**: nunca usar `meta_query` con
   `type: DATE` cuando el campo puede estar vacío. MySQL evalúa `'' < '2026-04-09'` = true.
   Usar siempre filtro PHP (`if ( $fin && $fin < $today )`) o añadir condición `!= ''`.

2. **CSS `!important` y especificidad**: `all: unset !important` + propiedades explícitas
   en un selector de clase doble (0,2,0) gana sobre cualquier clase única (0,1,0) aunque
   también tenga `!important`. Para sobrescribir: añadir la clase modificadora al selector
   base: `.base.modificador` (0,3,0).

3. **`wp_get_attachment_image_url()` no funciona con videos**: para adjuntos de tipo
   `video/*`, esta función devuelve `false`. Usar `get_post_mime_type()` para detectar
   y `wp_get_attachment_url()` para obtener la URL del video.

4. **Auto-inject y `explode('</p>')`**: el split parte cualquier `</p>` en el contenido,
   incluidos los que están dentro de `<blockquote>`, `<td>`, `<li>`. Hay dos capas de
   protección:
   - Pre-cálculo: `is_adjacent_to_image()` detecta blockquotes y lo excluye del mapa.
   - Salida: guardia en el bucle de ensamblado que comprueba el segmento siguiente.

5. **`is_adjacent_to_image()` — qué segmento mirar**:
   - `$parts[$index]` = contenido DESDE el `</p>` anterior HASTA el `</p>` actual.
     Una `<figure>` al INICIO de este segmento quedó entre el párrafo anterior y el actual,
     no entre el actual y el punto de inyección. Solo `<blockquote` bloquea aquí.
   - `$parts[$index+1]` = lo que viene DESPUÉS del punto de inyección. Aquí sí hay
     que comprobar `<figure>`, `<img>`, `</blockquote>`.

## Arquitectura clave

**Flujo de release** (nunca saltarte esto):
```
fix en main → git tag vX.Y.Z → git push origin vX.Y.Z
```
El tag dispara el GitHub Action → genera ZIP → sube al release de GitHub.
WordPress detecta la nueva versión vía Plugin Update Checker.

**El ZIP se construye con `build-zip.py`** (no usar PowerShell —
genera rutas con backslash que rompen en Linux/Plesk).

**CSS con `!important`**: los `!important` en `geoad-frontend.css` son
intencionales para sobrescribir Bricks Builder. No los quites sin probar en el site real.

**`wpautop`**: NO usar `ob_start()` para HTML de banners. Usar siempre
concatenación de strings (`$html .= '...'`).

**Badge "Publicidad"**:
- Por defecto: **oculto**. Solo se muestra si `_geo_mostrar_publicidad = '1'`.
- Cada `.geoad-banner` lleva `data-mostrar-publicidad="0|1"`
- El JS en `showNext()` hace toggle de `.geoad-label--hidden` en cada rotación
- La visibilidad inicial se calcula en PHP desde el primer banner (sin parpadeo)

**Estadísticas**: tabla `wp_geoad_stats`, datos agregados por día, retención 30 días.
⚠️ El cron `geo_aggregate_stats` purga datos >30 días, lo que afecta al "Total acumulado".
Pendiente de fix.

## Archivos principales

| Archivo | Para qué |
|---|---|
| `includes/class-shortcode-geoad.php` | Renderiza los banners en el frontend |
| `includes/class-auto-inject.php` | Inyección automática en artículos |
| `includes/class-cron-manager.php` | Caducidad automática de anuncios por fecha |
| `includes/class-stats-tracker.php` | Estadísticas + meta box de rendimiento |
| `includes/class-meta-boxes.php` | Tabs del editor de anuncios (incluye preview de video) |
| `includes/class-settings.php` | Página de ajustes del plugin |
| `assets/css/geoad-frontend.css` | CSS de banners (con !important intencionales) |
| `assets/js/geoad-rotation.js` | Rotación + lazy video + sticky dismiss + toggle badge |
| `.github/workflows/release.yml` | CI/CD — genera ZIP en cada tag |
| `build-zip.py` | Empaquetado (excluye archivos de desarrollo) |

## Decisiones no obvias

1. **`position: absolute` en `.geoad-banner`**: banners inactivos en absolute + `opacity: 0`.
   Evita el salto de layout al rotar.
2. **`geoad-wrap`**: wrapper que agrupa zona + label para que Bricks Builder no los separe.
3. **Inserción automática**: `explode('</p>', $content)`. `is_adjacent_to_image()` detecta
   `<img>`, `<figure>` y `<blockquote>` para no inyectar en sitios incorrectos.
4. **Plugin Update Checker con `enableReleaseAssets()`**: busca el ZIP adjunto al release,
   no el código fuente. El ZIP debe tener carpeta `geogastronomica/` como prefijo.

---

## Comandos útiles

```bash
# Crear nueva release
git tag v2.X.X && git push origin v2.X.X

# Ver GitHub Actions
gh run list --limit 5

# Probar el ZIP localmente
python build-zip.py
```

- `/piensa:continuar` — retomar el proyecto con todo el contexto
- `/piensa:estado` — ver estado detallado
- `/piensa:desarrollar` — empezar con la siguiente tarea
