# Investigacion: Plugins similares de gestion de anuncios en WordPress

> Proyecto: GeoGastronomica | Area: alternativas | Prioridad: baja

## Resumen ejecutivo

Los plugins existentes estan disenados para editores que trabajan con redes publicitarias externas (AdSense, Google Ad Manager). Ninguno esta orientado al caso de uso de GeoGastronomica: venta directa de espacios, formatos custom predefinidos, gestion por un unico editor no tecnico, sin integracion con redes externas. El nicho esta libre — GeoGastronomica puede diferenciarse con simplicidad radical y formatos propios.

## Hallazgos clave

1. **Complejidad excesiva como patron comun.** Advanced Ads, Ad Inserter y WP AdCenter tienen decenas de opciones pensadas para AdSense/redes externas. Un periodista no tecnico se perderia en sus interfaces.

2. **Ninguno ofrece formatos dimensionales predefinidos.** Todos trabajan con "bloques de codigo" o imagenes sueltas. Ningun plugin define un sistema de 4 formatos (vertical/cuadrado/horizontal/movil) con responsive automatico entre ellos — es el diferenciador clave de GeoGastronomica.

3. **El mas cercano al caso de uso es AdSanity.** Orientado a banner directo, interfaz limpia, shortcodes, scheduling. Pero cuesta $59-179/ano, no tiene concepto de "zona" con prioridad numerica ni rotacion multi-formato responsive.

4. **AdRotate es la referencia tecnica mas util.** Su sistema de grupos + shortcodes + rotacion basica con estadisticas es el patron mas parecido a lo que se va a construir. Version gratuita funcional.

5. **Estadisticas de impresiones/clicks son feature premium en todos.** En Advanced Ads y Ad Inserter requieren plan de pago. En AdRotate free tiene estadisticas basicas. Esto valida incluirlo en GeoGastronomica como diferenciador incluso en v1.

6. **Lazy loading ausente o premium.** Solo Advanced Ads Pro lo incluye. GeoGastronomica puede ofrecerlo nativo desde el inicio.

## Recomendacion

No adoptar ninguno de los plugins existentes. El caso de uso es lo suficientemente especifico (formatos fijos, zonas con prioridad, editor unico no tecnico, sin redes externas) como para justificar el desarrollo propio. Usar AdRotate como referencia conceptual para el sistema de grupos/shortcodes, y AdSanity como referencia de UX de admin.

Funcionalidades que GeoGastronomica puede ofrecer de serie que en la competencia son premium o inexistentes: responsive multi-formato automatico, lazy loading nativo, caducidad automatica, zonas con prioridad numerica.

## Alternativas consideradas

| Plugin | Orientacion | Formatos custom | Zonas/prioridad | Responsive auto | Precio | Lo que falta para nuestro caso |
|--------|-------------|-----------------|-----------------|-----------------|--------|-------------------------------|
| Advanced Ads | Redes externas + directo | No (codigo libre) | Si (grupos) | No nativo | Gratis / desde €89/ano Pro | Demasiado complejo, UI tecnica, sin formatos predefinidos |
| Ad Inserter | Redes externas, insercion automatica | No | No (bloques) | No | Gratis / $20 Pro | Sin concepto de zona ni rotacion entre formatos, muy tecnico |
| WP AdCenter | Redes externas + venta de espacios | No | Si | No | Gratis / ~$49/ano Pro | Pensado para marketplaces de anuncios, no para editor unico |
| AdSanity | Directo + redes | No | No | No | $59-179/ano | Sin zonas con prioridad, sin multi-formato responsive, de pago |
| AdRotate | Directo + redes | No | Si (grupos) | No | Gratis / €39/ano Pro | Sin formatos dimensionales, estadisticas basicas solo en Pro |

## Referencias

- [Advanced Ads – WordPress.org](https://wordpress.org/plugins/advanced-ads/)
- [Advanced Ads Pricing](https://wpadvancedads.com/pricing/)
- [Ad Inserter – WordPress.org](https://wordpress.org/plugins/ad-inserter/)
- [Ad Inserter Pro](https://adinserter.pro/)
- [WP AdCenter – WordPress.org](https://wordpress.org/plugins/wpadcenter/)
- [AdSanity](https://adsanityplugin.com/)
- [AdRotate Banner Manager – WordPress.org](https://wordpress.org/plugins/adrotate/)
- [8 Best WordPress Advertising Plugins 2025 – Elegant Themes](https://www.elegantthemes.com/blog/wordpress/best-advertising-plugins-for-wordpress)
- [6 Best WordPress Ad Management Plugins – WPBeginner](https://www.wpbeginner.com/plugins/what-are-the-best-ad-management-plugins-and-solutions-for-wordpress/)
