# Plan — T-001: Scaffolding del plugin y estructura OOP

## Descripcion
Crear la estructura de directorios del plugin, archivo principal con header WordPress, composer.json con classmap autoload, clase bootstrap (Singleton) que inicializa el plugin, y constantes globales.

## Criterios de aceptacion
- [ ] [restriccion] geogastronomica.php tiene header valido con Plugin Name, Version, Text Domain, Requires PHP 8.0
- [ ] [restriccion] composer.json define classmap autoload apuntando a includes/
- [ ] [restriccion] composer dump-autoload genera vendor/autoload.php sin errores
- [ ] [restriccion] Clase GeoGastronomica usa Singleton y se instancia en archivo principal
- [ ] [restriccion] Existen directorios: includes/, assets/css/, assets/js/, templates/, languages/
- [ ] [negativo] NO debe incluir dependencias externas en composer.json
- [ ] [negativo] NO debe activarse si PHP < 8.0

## Archivos a crear
- `geogastronomica.php` — archivo principal del plugin con header WP
- `composer.json` — autoload classmap
- `includes/class-geogastronomica.php` — clase bootstrap Singleton
- `uninstall.php` — placeholder con guard WP_UNINSTALL_PLUGIN

## Directorios a crear
- `includes/`
- `assets/css/`
- `assets/js/`
- `templates/`
- `languages/`

## Enfoque de implementacion
1. Crear directorios vacios con .gitkeep
2. Crear composer.json con classmap autoload
3. Crear clase GeoGastronomica con Singleton pattern
4. Crear geogastronomica.php con header, check PHP 8.0, require autoload, instanciar bootstrap
5. Crear uninstall.php con guard
6. Ejecutar composer dump-autoload

## Dependencias tecnicas
- Composer instalado en el sistema
- WordPress API (funciones como register_activation_hook, add_action, etc.)

## Notas
- Usar prefijo _geo_ para meta keys (definir como constante)
- Text Domain: geogastronomica
- Seguir WordPress Coding Standards para naming de archivos (class-nombre.php)
