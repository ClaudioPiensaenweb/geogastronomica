# Tests TDD Red Phase — Tarea T-001
# Generado por piensa-tester (modo declarativo)
# Todos estos comportamientos DEBEN verificarse tras implementar la tarea

## Criterio: Header del plugin valido

### [restriccion] Header con campos obligatorios
- **GIVEN** el archivo `geogastronomica.php` existe en la raiz del plugin
- **WHEN** WordPress lo parsea como plugin
- **THEN** contiene los campos: Plugin Name, Version, Text Domain (`geogastronomica`), Requires PHP (`8.0`), Description, Author
- **VERIFICACION**: `grep -E "Plugin Name:|Version:|Text Domain:|Requires PHP:" geogastronomica.php`

## Criterio: Composer autoload

### [restriccion] composer.json con classmap
- **GIVEN** el archivo `composer.json` existe en la raiz
- **WHEN** se lee el campo `autoload`
- **THEN** define `classmap` apuntando a `includes/`
- **VERIFICACION**: `cat composer.json | grep -A2 classmap`

### [restriccion] autoload genera vendor/autoload.php
- **GIVEN** `composer.json` esta configurado
- **WHEN** se ejecuta `composer dump-autoload`
- **THEN** se genera `vendor/autoload.php` sin errores
- **VERIFICACION**: `composer dump-autoload && test -f vendor/autoload.php`

## Criterio: Clase bootstrap Singleton

### [restriccion] Clase GeoGastronomica con Singleton
- **GIVEN** el archivo `includes/class-geogastronomica.php` existe
- **WHEN** se inspecciona la clase `GeoGastronomica`
- **THEN** implementa patron Singleton (metodo `get_instance()` estatico, constructor privado)
- **VERIFICACION**: `grep -E "private function __construct|public static function get_instance|private static \\\$instance" includes/class-geogastronomica.php`

### [restriccion] Bootstrap se instancia desde archivo principal
- **GIVEN** el archivo `geogastronomica.php` existe
- **WHEN** se inspecciona el archivo
- **THEN** llama a `GeoGastronomica::get_instance()` despues de `require vendor/autoload.php`
- **VERIFICACION**: `grep -E "require.*autoload|GeoGastronomica::get_instance" geogastronomica.php`

## Criterio: Estructura de directorios

### [restriccion] Directorios obligatorios existen
- **GIVEN** el plugin esta scaffoldeado
- **WHEN** se verifican los directorios
- **THEN** existen: `includes/`, `assets/css/`, `assets/js/`, `templates/`, `languages/`
- **VERIFICACION**: `for d in includes assets/css assets/js templates languages; do test -d "$d" || echo "FALTA: $d"; done`

## Criterio: Sin dependencias externas

### [negativo] composer.json NO tiene require
- **GIVEN** `composer.json` existe
- **WHEN** se inspecciona el campo `require`
- **THEN** NO existe o esta vacio (solo autoload, no dependencias externas)
- **VERIFICACION**: `cat composer.json | grep -c '"require"' | grep 0`

## Criterio: Check de version PHP

### [negativo] NO debe activarse con PHP < 8.0
- **GIVEN** el archivo `geogastronomica.php` existe
- **WHEN** se inspecciona el codigo de activacion
- **THEN** contiene check `version_compare(PHP_VERSION, '8.0', '<')` con `deactivate_plugins` y `admin_notice`
- **VERIFICACION**: `grep -E "version_compare.*PHP_VERSION.*8.0|deactivate_plugins" geogastronomica.php`

## Criterio: Uninstall placeholder

### [restriccion] uninstall.php existe con guard
- **GIVEN** el archivo `uninstall.php` existe
- **WHEN** se inspecciona
- **THEN** contiene check `defined('WP_UNINSTALL_PLUGIN')` al inicio
- **VERIFICACION**: `head -5 uninstall.php | grep "WP_UNINSTALL_PLUGIN"`

---

## Resumen de cobertura

| Criterio del ROADMAP | Test(s) asociado(s) |
|---------------------|---------------------|
| Header valido con Plugin Name, Version, Text Domain, Requires PHP 8.0 | Header con campos obligatorios |
| composer.json define classmap apuntando a includes/ | composer.json con classmap |
| composer dump-autoload genera vendor/autoload.php sin errores | autoload genera vendor/autoload.php |
| Clase GeoGastronomica usa Singleton, se instancia en archivo principal | Clase Singleton + Bootstrap instancia |
| Directorios includes/, assets/css/, assets/js/, templates/, languages/ | Directorios obligatorios existen |
| NO debe incluir dependencias externas en composer.json | composer.json NO tiene require |
| NO debe activarse si PHP < 8.0 | Check de version PHP |
| uninstall.php existe con guard WP_UNINSTALL_PLUGIN | uninstall.php placeholder |

**Criterios cubiertos: 7/7 (100%)**
