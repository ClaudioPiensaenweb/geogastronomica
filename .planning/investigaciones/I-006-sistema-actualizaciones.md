# Investigacion: Sistema de actualizaciones para plugin fuera de wordpress.org

> Proyecto: GeoGastronomica | Area: patrones | Prioridad: media

## Resumen ejecutivo

Para un plugin privado que no se distribuye en wordpress.org, la solucion mas establecida y de menor friccion es incrustar la libreria **plugin-update-checker** (YahnisElsts, MIT, 2.5k stars, activa en 2025) directamente en el plugin. Apunta a GitHub Releases como fuente de verdad: cuando se publica un release con un ZIP adjunto, WordPress detecta la nueva version y ofrece el boton de actualizar en el panel de administracion — exactamente igual que con plugins del repositorio oficial.

## Hallazgos clave

1. **plugin-update-checker es el estandar de facto** para plugins privados: version 5.6 publicada mayo 2025, licencia MIT, mas de 1.2M de installs en Packagist. Se incluye como vendor dentro del propio plugin, sin dependencias externas.

2. **Integracion con GitHub Releases en 4 lineas de PHP**: se instancia `PucFactory::buildUpdateChecker()` con la URL del repositorio, la ruta del archivo principal del plugin y un slug unico. La libreria usa el campo `Version:` del header del plugin para comparar versiones.

3. **El hook nativo `pre_set_site_transient_update_plugins` existe pero es fragil**: requiere implementar manualmente la logica de comparacion de versiones, el formato de respuesta JSON y el servidor que sirve el ZIP. Viable pero introduce superficie de mantenimiento innecesaria para un plugin de un solo sitio.

4. **Git Updater (afragen) es un plugin separado** que hay que instalar en el sitio destino — crea una dependencia de instalacion adicional para el administrador del sitio. No recomendado cuando se puede incrustar la libreria directamente.

5. **wp-update-server (YahnisElsts)** es la solucion complementaria de servidor propio: necesario solo si se quiere control total sobre licencias o actualizaciones de pago. Para un plugin de uso interno en un sitio, GitHub Releases lo sustituye sin coste adicional.

6. **Repositorio privado en GitHub**: plugin-update-checker soporta repositorios privados via token de acceso personal. Si el repo es publico, no se necesita configuracion adicional.

## Recomendacion

Incrustar **plugin-update-checker v5.x** en la carpeta `vendor/` del plugin y publicar actualizaciones via **GitHub Releases** con el ZIP del plugin como release asset. Es la opcion con menor friccion de mantenimiento: un `git tag` + release en GitHub es suficiente para que WordPress muestre la notificacion de actualizacion en el admin.

```php
// En el archivo principal del plugin
require 'vendor/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

$updateChecker = PucFactory::buildUpdateChecker(
    'https://github.com/tu-usuario/geogastronomica/',
    __FILE__,
    'geogastronomica'
);
$updateChecker->getVcsApi()->enableReleaseAssets();
```

Para publicar una actualizacion: incrementar `Version:` en el header del plugin, crear un ZIP, publicar GitHub Release con el ZIP adjunto.

## Alternativas consideradas

| Opcion | Pros | Contras |
|--------|------|---------|
| plugin-update-checker + GitHub Releases | Libreria madura, MIT, cero infraestructura, flujo nativo de WP | Hay que incluir ~150KB de vendor en el plugin |
| Hook nativo `pre_set_site_transient_update_plugins` | Sin dependencias externas | Implementacion manual fragil, servidor propio necesario |
| Git Updater (afragen) | Soporta multiples repos y VCS | Plugin separado que instalar en el sitio; dependencia externa |
| wp-update-server (YahnisElsts) | Control total, soporte de licencias | Requiere servidor PHP propio para alojar el servidor de updates |
| WordPress.org | Actualizaciones nativas sin configuracion | Plugin debe ser publico; proceso de revision; no aplica aqui |

## Referencias

- [YahnisElsts/plugin-update-checker — GitHub](https://github.com/YahnisElsts/plugin-update-checker)
- [YahnisElsts/wp-update-server — GitHub](https://github.com/YahnisElsts/wp-update-server)
- [afragen/git-updater — GitHub](https://github.com/afragen/git-updater)
- [Self-Hosted WordPress Plugin Updates — bebic.dev](https://www.bebic.dev/blog/self-hosted-wordpress-plugin-updates)
- [Build Your Own WordPress Plugin Update Server with a Serverless Function — macarthur.me](https://macarthur.me/posts/serverless-wordpress-plugin-update-server/)
