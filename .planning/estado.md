# Estado del Proyecto — GeoGastronomica Plugin

> Última actualización: 2026-03-30 por Piensaenweb
> Handoff preparado: sí

---

## Fase actual

**Fase 2 completada → Fase 3 pendiente**

La fase 2 (estabilización y polish) quedó completada hoy con v1.9.9.
La siguiente fase son mejoras orientadas a análisis y automatización.

## Progreso

`████████████████████░░░░` ~80% del producto mínimo viable

- **Completadas**: todas las de Fase 1 y Fase 2
- **En progreso**: ninguna
- **Pendientes**: Fase 3 (mejoras) y Fase 4 (futuras)

## Qué se hizo hoy (última sesión — 2026-03-30)

- ✓ Sistema de estimación de impresiones: barra de progreso vs contratadas, ritmo diario, fecha estimada
- ✓ Campo "Impresiones contratadas" en tab Configuración del anuncio
- ✓ Badge "Publicidad" rediseñado como absoluto dentro del banner (esquina inferior-derecha)
- ✓ `all: unset !important` en el badge para aislarlo de estilos del tema
- ✓ URL de política de privacidad configurable en Ajustes (RGPD)
- ✓ `geoad-wrap` como wrapper del shortcode para evitar que Bricks oculte el label
- ✓ README con flujo de desarrollo, estructura del proyecto y tabla "qué se sobreescribe"
- ✓ Releases v1.9.6 → v1.9.7 → v1.9.8 → v1.9.9 publicados en GitHub

## Siguiente tarea recomendada

**Comparativa de anuncios** — página en el admin que muestra todos los anuncios activos
ordenados por CTR e impresiones, para que el gestor del sitio vea de un vistazo
qué anuncios funcionan mejor. Datos ya disponibles en `wp_geoad_stats`.

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

**Estadísticas**: la tabla `wp_geoad_stats` guarda datos agregados por día.
Retención: 30 días. El cron de purga se llama `geo_aggregate_stats`.
No hay cookies ni tracking del lado del cliente — todo es server-side.

### Archivos principales

| Archivo | Para qué |
|---|---|
| `includes/class-shortcode-geoad.php` | Renderiza los banners en el frontend |
| `includes/class-stats-tracker.php` | Estadísticas + meta box de rendimiento |
| `includes/class-meta-boxes.php` | Tabs del editor de anuncios |
| `includes/class-settings.php` | Página de ajustes del plugin |
| `assets/css/geoad-frontend.css` | CSS de banners (con !important intencionales) |
| `assets/js/geoad-rotation.js` | Rotación + lazy video + sticky dismiss |
| `.github/workflows/release.yml` | CI/CD — genera ZIP en cada tag |
| `build-zip.py` | Empaquetado local (excluye archivos de desarrollo) |

### Decisiones tomadas que no son obvias

1. **`position: absolute` en `.geoad-banner`**: los banners inactivos están en absolute
   para que no ocupen espacio. El activo tiene `opacity: 1`, los demás `opacity: 0`.
   Esto evita el salto de layout al rotar.

2. **`geoad-wrap`**: wrapper añadido para que Bricks Builder no separe el badge
   "Publicidad" de la zona del banner al renderizar el shortcode.

3. **Plugin Update Checker con `enableReleaseAssets()`**: el checker busca el ZIP
   adjunto al release de GitHub, no el código fuente. Por eso el ZIP debe tener
   la carpeta `geogastronomica/` como prefijo o WordPress no instala bien.

---

## Comandos útiles

```bash
# Ver estado del repo
git log --oneline -10

# Crear nueva release
git tag v2.0.0 && git push origin v2.0.0

# Probar el ZIP localmente
python build-zip.py

# Ver GitHub Actions
gh run list --limit 5
```

- `/piensa:continuar` — retomar el proyecto con todo el contexto
- `/piensa:estado` — ver estado detallado
