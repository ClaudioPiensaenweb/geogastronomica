# Estado del Proyecto — GeoGastronomica Plugin

> Última actualización: 2026-04-06 por Piensaenweb
> Handoff preparado: sí

---

## Fase actual

**Fase 3 en progreso**

Dos tareas de Fase 3 completadas en esta sesión. Quedan las mejoras de análisis y automatización.

## Progreso

`█████████████████████░░░` ~85% del producto mínimo viable

- **Completadas**: Fase 1, Fase 2, + 2 tareas de Fase 3
- **En progreso**: ninguna
- **Pendientes**: resto de Fase 3 y Fase 4

## Qué se hizo hoy (última sesión — 2026-04-06)

- ✓ Inserción automática no pegada a imágenes — detecta `<img>` y `<figure>` (Gutenberg y editor clásico), desliza hasta 4 párrafos para no romper la estética (v2.0.0)
- ✓ Check "mostrar badge Publicidad" por anuncio — campo `_geo_mostrar_publicidad` en tab Config (v2.0.0)
- ✓ Badge sincronizado con rotación — `data-mostrar-publicidad` en cada banner + toggle en JS con `.geoad-label--hidden` (v2.0.1)
- ✓ PW Compresor v1.1.0 — GIFs excluidos del procesado, borrado del original tras conversión exitosa, no convierte si WebP pesa más, funciones helper para eliminar duplicación

## Siguiente tarea recomendada

**Comparativa de anuncios** — página en el admin que muestra todos los anuncios activos
ordenados por CTR e impresiones. Datos ya disponibles en `wp_geoad_stats`.

## Notas para el siguiente dev

### Arquitectura clave que debes entender

**Flujo de release** (nunca saltarte esto):
```
rama feat/... → PR → merge a main → git tag vX.Y.Z → git push origin vX.Y.Z
```
El tag dispara el GitHub Action que genera el ZIP y lo sube al release.
WordPress detecta la nueva versión vía Plugin Update Checker (vendor/yahnis-elsts/plugin-update-checker).

**El ZIP se construye con `build-zip.py`** (no usar PowerShell Compress-Archive —
en el pasado generaba rutas con backslash que rompían la instalación en Linux/Plesk).

**CSS con `!important`**: hay varios `!important` en `geoad-frontend.css` que son
intencionales para sobrescribir estilos del tema (Bricks Builder aplica márgenes
y posicionamientos que rompen los banners). No los quites sin probar en el site real.

**`wpautop` y los templates PHP**: NO uses `ob_start()` para generar HTML de banners.
`wpautop` convierte líneas en blanco en `<br>`/`<p>` dentro del buffer. Usar siempre
concatenación de strings (`$html .= '...'`).

**Badge "Publicidad"**:
- Cada `.geoad-banner` lleva `data-mostrar-publicidad="0|1"`
- El JS en `showNext()` hace toggle de `.geoad-label--hidden` en cada rotación
- La visibilidad inicial se calcula en PHP desde el primer banner (sin parpadeo)
- Campo `_geo_mostrar_publicidad` en tab Configuración del anuncio

**Estadísticas**: la tabla `wp_geoad_stats` guarda datos agregados por día.
Retención: 30 días. El cron de purga se llama `geo_aggregate_stats`.
No hay cookies ni tracking del lado del cliente — todo es server-side.

**PW Compresor** (plugin separado):
- v1.1.0 — GIFs excluidos, borra original tras WebP exitoso, no convierte si WebP pesa más
- Pendiente: bulk processing de imágenes existentes y limpieza de thumbnails

### Archivos principales

| Archivo | Para qué |
|---|---|
| `includes/class-shortcode-geoad.php` | Renderiza los banners en el frontend |
| `includes/class-auto-inject.php` | Inyección automática en artículos |
| `includes/class-stats-tracker.php` | Estadísticas + meta box de rendimiento |
| `includes/class-meta-boxes.php` | Tabs del editor de anuncios |
| `includes/class-settings.php` | Página de ajustes del plugin |
| `assets/css/geoad-frontend.css` | CSS de banners (con !important intencionales) |
| `assets/js/geoad-rotation.js` | Rotación + lazy video + sticky dismiss + toggle badge |
| `.github/workflows/release.yml` | CI/CD — genera ZIP en cada tag |
| `build-zip.py` | Empaquetado local (excluye archivos de desarrollo) |

### Decisiones tomadas que no son obvias

1. **`position: absolute` en `.geoad-banner`**: los banners inactivos están en absolute
   para que no ocupen espacio. El activo tiene `opacity: 1`, los demás `opacity: 0`.
   Esto evita el salto de layout al rotar.

2. **`geoad-wrap`**: wrapper que agrupa zona + label para que Bricks Builder no los separe.

3. **Inserción automática**: usa `explode('</p>', $content)` — cada parte es el texto
   antes de `</p>`. `is_adjacent_to_image()` comprueba si el párrafo actual o el
   siguiente contiene `<img>` o `<figure>`.

4. **Plugin Update Checker con `enableReleaseAssets()`**: busca el ZIP adjunto al release,
   no el código fuente. El ZIP debe tener la carpeta `geogastronomica/` como prefijo.

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
