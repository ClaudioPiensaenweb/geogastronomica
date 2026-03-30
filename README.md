# GeoGastronomica — Plugin de Banners

Plugin WordPress para gestión de banners publicitarios propios. Permite crear anuncios con múltiples formatos (horizontal, móvil, vertical), rotación automática, estadísticas de impresiones/clicks y cumplimiento legal (LSSI Art. 20).

## Instalación en producción

1. Ve a la página de [Releases](../../releases) de este repositorio
2. Descarga el archivo `geogastronomica.zip` de la última versión
3. En WordPress: **Plugins → Subir plugin → Instalar**
4. O activa las actualizaciones automáticas — el plugin se actualiza solo desde este repositorio

> Las actualizaciones **nunca sobreescriben datos**: anuncios, ajustes y estadísticas viven en la base de datos y son independientes del código.

---

## Desarrollo

### Requisitos

- PHP 8.0+
- Composer
- Python 3 (para construir el ZIP localmente)
- Git

### Setup local

```bash
git clone https://github.com/ClaudioPiensaenweb/geogastronomica.git
cd geogastronomica
composer install
```

### Flujo de trabajo

```
feature/mi-cambio → PR → main → tag vX.Y.Z → GitHub Action → ZIP → WordPress
```

**Regla clave:** el plugin en WordPress solo se actualiza cuando se publica un tag. Los commits en `main` sin tag no lanzan ninguna actualización.

#### 1. Desarrollar en rama

```bash
git checkout -b feat/descripcion-del-cambio
# ... hacer cambios ...
git add archivo.php
git commit -m "feat: descripcion del cambio (vX.Y.Z)"
git push -u origin feat/descripcion-del-cambio
```

#### 2. Merge a main

```bash
gh pr create --title "feat: descripcion"
gh pr merge --merge --delete-branch
```

#### 3. Publicar release (lanza la actualización a WordPress)

```bash
git checkout main && git pull origin main
git tag vX.Y.Z
git push origin vX.Y.Z
```

El GitHub Action hace automáticamente:
- `composer install --no-dev`
- `python build-zip.py` → genera el ZIP con la estructura correcta
- Sube el ZIP al release de GitHub

En ~2 minutos aparece la actualización en el panel de WordPress.

---

## Estructura del proyecto

```
geogastronomica/
├── assets/
│   ├── css/
│   │   ├── geoad-frontend.css      # Estilos de banners (frontend)
│   │   └── admin-meta-boxes.css    # Estilos del admin
│   └── js/
│       ├── geoad-rotation.js       # Rotación de banners + lazy video
│       └── admin-meta-boxes.js     # Media picker en admin
├── includes/
│   ├── class-geogastronomica.php   # Bootstrap del plugin
│   ├── class-cpt-anuncio.php       # Custom Post Type
│   ├── class-shortcode-geoad.php   # Shortcode [geoad zone="..."]
│   ├── class-meta-boxes.php        # Tabs de edición (empresa/anuncio/config)
│   ├── class-settings.php          # Página de ajustes
│   ├── class-stats-tracker.php     # Estadísticas (impresiones/clicks)
│   └── class-cache-manager.php     # Caché de consultas
├── vendor/                         # Dependencias Composer (PUC)
├── geogastronomica.php             # Cabecera del plugin
├── uninstall.php                   # Limpieza al desinstalar
├── build-zip.py                    # Script de empaquetado
└── composer.json
```

---

## Uso del shortcode

```
[geoad zone="home_horizontal_1"]
[geoad zone="home_vertical_1"]
[geoad zone="subcategoria_horizontal_1" sticky="bottom"]
[geoad zone="home_horizontal_1" format="horizontal"]
```

**Zonas disponibles:**

| Página | Zonas |
|---|---|
| `home` | `vertical_1`, `horizontal_1`, `horizontal_2` |
| `categoria` | `horizontal_1`, `horizontal_2` |
| `subcategoria` | `vertical_1`, `vertical_2`, `vertical_3`, `horizontal_1` |

---

## Qué se sobreescribe en una actualización y qué no

| Qué | ¿Se sobreescribe? | Dónde vive |
|---|---|---|
| Código PHP/CSS/JS | ✅ Sí (correcto) | Archivos del plugin |
| Anuncios creados | ❌ No | `wp_posts` + `wp_postmeta` |
| Ajustes del plugin | ❌ No | `wp_options` |
| Estadísticas | ❌ No | `wp_geoad_stats` (tabla custom) |
| Imágenes subidas | ❌ No | `wp-content/uploads/` |

---

## Licencia

Uso interno — GeoGastronomica / Piensaenweb.
