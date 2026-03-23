# Investigacion: Testing en plugins WordPress

> Proyecto: GeoGastronomica | Area: stack | Prioridad: media

## Resumen ejecutivo

Para un plugin PHP sin dependencias de frameworks JS, la combinacion optima es **PHPUnit + Brain Monkey** para tests unitarios (logica pura sin WordPress cargado) y **@wordpress/env + wp scaffold plugin-tests** para tests de integracion con WordPress real. El coste de setup es bajo y cubre los tres casos criticos del briefing: logica de seleccion de anuncios, caducidad por fecha y renderizado de shortcodes.

## Hallazgos clave

1. **PHPUnit 10/11 via Composer es el estandar actual.** `wp scaffold plugin-tests` genera el scaffolding inicial (bin/install-wp-tests.sh, tests/bootstrap.php, tests/test-sample.php) y sigue siendo la ruta oficial en 2025. No esta deprecado.

2. **Brain Monkey supera a WP_Mock para logica de seleccion/caducidad.** Permite mockear funciones globales de WP (`get_posts`, `get_post_meta`, `current_time`) sin levantar WordPress, lo que hace los tests rapidos y deterministicos. La comunidad lo prefiere sobre WP_Mock para manejar hooks y filtros.

3. **@wordpress/env (wp-env) es el entorno recomendado para integracion.** Provee Docker con WordPress + MySQL preconfigurado, incluye PHPUnit y WP_TESTS_DIR apuntando al test suite de WP Core. Elimina dependencia de SVN manual o instalar wp-tests a mano. Requiere Docker Desktop.

4. **`yoast/wp-test-utils` resuelve la compatibilidad PHPUnit/WP cross-version.** Especialmente util si se quiere soportar WP 6.x con PHP 8.0+: maneja el cambio de setUp()/tearDown() a set_up()/tear_down() introducido en WP 5.9.

5. **Tests de integracion con WP real son mas valiosos que puros unitarios para shortcodes.** Verificar que `[geoad zone="home_vertical_1"]` renderiza el HTML correcto requiere WP cargado (do_shortcode, get_posts, meta boxes activos). Brain Monkey no sirve para esto — necesita wp-env o similar.

## Recomendacion

Implementar dos niveles de test:

**Nivel 1 — Unitarios (sin WP):** PHPUnit + Brain Monkey para la clase que selecciona anuncios activos (filtra por fecha_inicio <= hoy <= fecha_fin, ordena por prioridad). Tests rapidos, sin Docker, ejecutables en CI sin setup.

**Nivel 2 — Integracion (con WP):** @wordpress/env + PHPUnit nativo del suite de WP para shortcodes y registro del CPT. Se ejecuta con `wp-env run tests-cli phpunit`.

Estructura de carpetas recomendada:
```
tests/
  bootstrap.php          # carga WP test suite o Brain Monkey segun nivel
  unit/
    test-ad-selector.php # logica de seleccion y caducidad (Brain Monkey)
  integration/
    test-shortcode.php   # renderizado de [geoad zone="..."] (WP real)
bin/
  install-wp-tests.sh    # generado por wp scaffold plugin-tests
```

## Alternativas consideradas

| Opcion | Pros | Contras |
|--------|------|---------|
| PHPUnit + Brain Monkey (recomendado) | Tests rapidos, sin Docker para unitarios, buena DX | Requiere instalar Composer en el proyecto |
| PHPUnit + WP_Mock (10up) | Similar a Brain Monkey, respaldo de 10up | Sintaxis mas verbosa, menos activo en 2024-25 |
| Solo @wordpress/env + integracion | Un solo setup, WP real | Tests lentos, Docker obligatorio para cualquier test |
| Pest PHP | Sintaxis moderna, popular en Laravel | Menos ejemplos en ecosistema WP, capa extra |

## Referencias

- [wp scaffold plugin-tests — WP-CLI oficial](https://developer.wordpress.org/cli/commands/scaffold/plugin-tests/)
- [How to add automated unit tests to your plugin — WordPress Developer Blog (dic 2025)](https://developer.wordpress.org/news/2025/12/how-to-add-automated-unit-tests-to-your-wordpress-plugin/)
- [Unit Testing WordPress Plugins in 2025 with @wordpress/env — Nate Weller](https://blog.nateweller.com/2025/05/09/unit-testing-wordpress-plugins-in-2025-with-wordpress-env-and-phpunit/)
- [Brain Monkey — documentacion oficial](https://giuseppe-mazzapica.gitbook.io/brain-monkey)
- [yoast/wp-test-utils — GitHub](https://github.com/Yoast/wp-test-utils)
- [@wordpress/env — Block Editor Handbook](https://developer.wordpress.org/block-editor/reference-guides/packages/packages-env/)
