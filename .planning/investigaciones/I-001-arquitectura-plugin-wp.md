# Investigacion: Arquitectura de plugin WordPress moderno

> Proyecto: GeoGastronomica | Area: stack | Prioridad: alta

## Resumen ejecutivo

Para un plugin WordPress de complejidad media (CPT + shortcodes + logica de rotacion), la arquitectura OOP con namespaces PSR-4 via Composer es el estandar recomendado en 2025. El WordPress Plugin Boilerplate (WPPB) es una buena base pero introduce overhead innecesario para este alcance; un esqueleto propio ligero inspirado en sus principios es la opcion mas pragmatica. El patron Singleton para la clase principal es aceptable, pero las clases internas deben usar inyeccion de dependencias para mantener testabilidad.

## Hallazgos clave

1. **OOP + namespaces es el estandar actual**: WordPress Developer Blog publico en septiembre 2025 una guia oficial sobre namespaces y coding standards. PSR-4 autoloading via Composer es la forma recomendada de cargar clases. La unica tension es que PSR-4 usa `NombreClase.php` mientras WordPress Coding Standards piden `class-nombre-clase.php` — la solucion pragmatica es usar `classmap` en Composer o adoptar PSR-4 puro y documentar la excepcion.

2. **Singleton para bootstrap, DI para el resto**: El patron mas extendido es una clase principal (bootstrap) con `get_instance()` que inicializa el plugin, y clases internas que reciben sus dependencias por constructor. Evitar el "God Class" que crea todas las dependencias internamente — dificulta testing y viola SRP (Single Responsibility Principle). Para este plugin: una clase `GeoGastronomica` de bootstrap, y clases separadas para `CPT`, `Shortcodes`, `AdminColumns`, `BannerQuery`.

3. **WPPB tiene deuda de mantenimiento**: El repositorio oficial (DevinVinson/WordPress-Plugin-Boilerplate) lleva sin actualizacion significativa desde 2022. La version "powered" (`wpbp.github.io`) esta mas activa pero es mas compleja. Para un plugin de alcance definido como este, un esqueleto propio de ~5 clases es mas mantenible que adoptar un boilerplate con abstracciones que no se van a usar.

## Recomendacion

Construir el plugin desde cero con la siguiente estructura, siguiendo principios del boilerplate pero sin adoptarlo como dependencia:

```
geogastronomica/
├── geogastronomica.php          # Header + bootstrap (require autoload, instanciar main class)
├── uninstall.php                # Limpieza de BD al desinstalar
├── composer.json                # PSR-4 autoloading
├── src/
│   ├── Plugin.php               # Clase principal, singleton, registra hooks
│   ├── PostType/
│   │   └── AdPostType.php       # Registro CPT + meta boxes
│   ├── Admin/
│   │   ├── MetaBoxes.php        # Tabs info empresa / anuncio / configuracion
│   │   └── AdminColumns.php     # Columnas en listado de anuncios
│   ├── Frontend/
│   │   ├── ShortcodeRenderer.php # Logica de shortcodes [geoad zone="..."]
│   │   └── BannerQuery.php      # Query de anuncios activos por zona/fecha/prioridad
│   └── Assets/
│       └── AssetsLoader.php     # Encolar CSS/JS solo donde se necesitan
├── assets/
│   ├── css/geogastronomica.css  # Estilos frontend (rotacion fade, responsive)
│   └── js/geogastronomica.js    # Rotacion JS vanilla
└── vendor/                      # Composer autoload
```

Usar PHP 8.0+ con type declarations en todas las funciones. Namespaces: `GeoGastronomica\PostType`, `GeoGastronomica\Admin`, `GeoGastronomica\Frontend`.

## Alternativas consideradas

| Opcion | Pros | Contras |
|--------|------|---------|
| WordPress Plugin Boilerplate (WPPB) | Estructura probada, familiar para otros devs | Sin mantenimiento activo desde 2022, overhead para plugin de este alcance |
| WPPB Powered | Mas moderno, generador online | Mas complejo, abstracciones innecesarias para un plugin cerrado |
| Esqueleto propio OOP (recomendado) | Control total, sin deuda de terceros, adaptado al alcance | Requiere definir la estructura desde cero (coste inicial bajo) |
| Procedural puro | Simple, familiar para devs legacy | No testeable, colisiones de nombres, no escalable |

## Referencias

- [WordPress Developer Blog — Namespaces y coding standards (sep 2025)](https://developer.wordpress.org/news/2025/09/implementing-namespaces-and-coding-standards-in-wordpress-plugin-development/)
- [Plugin Handbook — Best Practices](https://developer.wordpress.org/plugins/plugin-basics/best-practices/)
- [WordPress Plugin Boilerplate (WPPB)](https://wppb.me/)
- [WPPB Powered](https://wpbp.github.io/)
- [PSR-4 autoloading en plugins — DLX Plugins](https://dlxplugins.com/tutorials/creating-a-psr-4-autoloading-wordpress-plugin/)
- [Singletons vs DI en WordPress — Plugin Machine](https://pluginmachine.com/course/refactoring-wordpress-plugins/singletons-dependnecy-injection-in-wordpress/)
- [Building Advanced WordPress Plugins — BuddyX](https://buddyxtheme.com/building-advanced-wordpress-plugins-oop-namespaces-autoloading-and-modern-architecture/)
