# Briefing Tecnico — GeoGastronomica

> Generado por piensa v3.0.0
> Fecha: 2026-03-23
> Perfil usuario: tecnico

---

## Datos del proyecto

- **Nombre**: GeoGastronomica [inferido]
- **Cliente**: No especificado
- **Tipo**: Plugin WordPress [inferido]
- **Stack detectado**: Pendiente de definir
- **Repositorio**: No especificado

---

## Problema que resolvemos

Los blogs necesitan monetizar con publicidad pero Google Ads destroza el diseno de la pagina y no da control sobre que se muestra. GeoGastronomica es un sistema de gestion de anuncios/banners propios donde los espacios publicitarios se venden directamente a empresas, manteniendo el control total del diseno.

Actualmente existe una version funcional hecha a medida para zaragoza-ciudad.com usando un Custom Content Type (JetEngine) + Bricks Builder con query loops. El problema es que la logica de visualizacion esta acoplada a Bricks, no es reutilizable en otros sitios, y necesita optimizacion.

El objetivo es convertir esto en un plugin WordPress estandar, desacoplado de cualquier page builder, con shortcodes para insertar los banners y logica de rotacion/prioridad independiente.

---

## Usuarios y roles

| Rol | Descripcion | Permisos |
|-----|-------------|----------|
| Editor/Admin del blog | Periodista veterano (radio/prensa) que gestiona geogastronomica.com. Perfil no tecnico. Unico usuario del plugin. | CRUD completo de anuncios, configuracion de zonas, fechas y prioridades |

No hay panel para anunciantes — el editor gestiona todo manualmente desde el admin de WordPress.

---

## Requisitos funcionales

### Funcionalidades existentes (migrar del sistema actual)
- Custom Post Type con panel de administracion organizado en tabs
- **Info empresa**: nombre, email, telefono del anunciante
- **Anuncio**: descripcion, enlace destino, 4 formatos de banner:
  - Vertical (285x627)
  - Cuadrado (285x285)
  - Horizontal (1230x350)
  - Movil (1000x400)
- **Configuracion**: fechas inicio/fin de campana, seleccion de zonas de aparicion (home, categoria, subcategoria/articulo/autor), slots por zona, prioridad numerica
- Rotacion de anuncios con transicion CSS (opacity fade)

### Mejoras confirmadas
- Shortcodes por tipo de zona (en vez de depender de Bricks Builder/query loops)
- Responsive nativo: el banner adecuado se muestra segun viewport
- Logica de visualizacion desacoplada de cualquier page builder

### Funcionalidades a investigar (el usuario esta abierto)
- Estadisticas basicas de impresiones/clicks
- Caducidad automatica (desactivar anuncios al llegar fecha_fin)
- Lazy loading de banners para rendimiento
- Otras mejoras que surjan de la investigacion

---

## Que NO incluye

- No es un marketplace de anunciantes (no hay registro ni autoservicio para empresas)
- No integra con redes publicitarias externas (Google Ads, AdSense, etc.)
- No incluye sistema de pagos ni facturacion
- No tiene panel frontend para anunciantes — toda la gestion es desde wp-admin
- No incluye sistema de pujas ni subastas por espacios
- No genera banners automaticamente — las creatividades las sube el editor manualmente
- No incluye A/B testing de creatividades

---

## Requisitos no funcionales

- **Rendimiento**: Los banners no deben impactar significativamente el tiempo de carga (lazy loading recomendado)
- **Compatibilidad**: WordPress 6.x+, PHP 8.0+. Independiente de page builder (Bricks, Elementor, Gutenberg, etc.)
- **UX Admin**: Interfaz sencilla para usuario no tecnico. Tabs organizados como en el sistema actual
- **Responsive**: Servir el formato de banner adecuado segun viewport sin CSS hacks
- **SEO**: Los banners no deben generar CLS (Cumulative Layout Shift) — reservar espacio antes de cargar
- **Mantenibilidad**: Codigo bien estructurado siguiendo WordPress Coding Standards
- **Testing**: Pendiente de definir en la investigacion

---

## Flujos principales

### Flujo: Crear un anuncio nuevo
1. Editor accede a wp-admin → GeoGastronomica → Nuevo anuncio
2. Rellena tab "Info empresa" (nombre, email, telefono)
3. Rellena tab "Anuncio" (descripcion, enlace, sube banners en los formatos necesarios)
4. Configura tab "Configuracion" (fechas, zonas, prioridad)
5. Publica el anuncio → activo inmediatamente si fecha_comienzo <= hoy

### Flujo: Insertar zona de anuncios en el sitio
1. Editor copia shortcode (ej: `[geoad zone="home_vertical_1"]`)
2. Lo pega en el contenido, widget o template donde quiera el banner
3. El plugin renderiza el banner activo con mayor prioridad para esa zona
4. Si hay multiples anuncios en la zona, rotan con transicion fade

### Flujo: Anuncio caduca
1. Llega la fecha_fin del anuncio
2. El plugin deja de mostrarlo automaticamente
3. En el listado de admin aparece como caducado/inactivo

---

## Prioridades (MoSCoW)

### Must have (imprescindible)
- Custom Post Type con meta boxes organizados en tabs (info empresa, anuncio, configuracion)
- 4 formatos de banner (vertical, cuadrado, horizontal, movil)
- Shortcodes para insertar zonas de anuncios
- Configuracion de fechas inicio/fin
- Seleccion de zonas (home, categoria, subcategoria/articulo/autor)
- Prioridad numerica entre anuncios
- Responsive: banner adecuado segun viewport
- Caducidad automatica por fecha_fin

### Should have (importante)
- Rotacion de anuncios con transicion CSS fade
- Lazy loading de imagenes de banners
- Listado en admin con columnas: empresa, descripcion, fecha inicio, fecha fin, estado
- Acciones en lote (activar, desactivar, eliminar)

### Could have (deseable)
- Estadisticas basicas de impresiones/clicks
- Shortcode con preview en el editor de bloques (Gutenberg)
- Cache de queries para rendimiento
- Exportar/importar anuncios

### Won't have (fuera de alcance)
- Panel de autoservicio para anunciantes
- Integracion con redes publicitarias externas
- Sistema de pagos/facturacion
- A/B testing de creatividades
- Marketplace de espacios publicitarios

---

## Metricas de exito

| Metrica | Objetivo | Como se mide |
|---------|----------|------------|
| Usabilidad | El editor gestiona anuncios sin ayuda tecnica | 0 tickets de soporte relacionados con el plugin |
| Rendimiento | Banners no impactan el tiempo de carga | Lighthouse Performance > 90 con banners activos |
| Fiabilidad | Los anuncios se muestran en las zonas correctas | Test manual en todas las zonas (home, categoria, articulo) |
| Caducidad | Anuncios caducados no se muestran | Verificar que pasada fecha_fin el banner desaparece |
| Compatibilidad | Funciona sin Bricks ni ningun page builder | Shortcodes renderizan correctamente en cualquier tema |
| Responsive | Banner adecuado segun dispositivo | Verificar en mobile, tablet y desktop |

---

## Arquitectura propuesta

Pendiente de investigacion. Linea base esperada:
- Plugin WordPress estandar (PHP nativo)
- Custom Post Type con meta boxes para datos del anuncio
- Shortcodes para renderizar zonas de anuncios en frontend
- JS vanilla para rotacion/transiciones
- CSS responsive para adaptar formato segun viewport
- Sin dependencia de page builders ni frameworks JS

---

## Integraciones

No hay integraciones externas en esta fase. El plugin es autocontenido dentro de WordPress.

---

## Restricciones tecnicas

- Debe funcionar en WordPress 6.x+ con PHP 8.0+
- No debe depender de ningun page builder (Bricks, Elementor, etc.)
- No debe depender de JetEngine ni otros plugins de CPT — el plugin registra su propio CPT
- Las imagenes se gestionan con la Media Library nativa de WordPress
- El plugin debe ser activable/desactivable sin dejar basura en la BD (uninstall limpio)
- Debe seguir WordPress Coding Standards
- Mecanismo de actualizacion: el plugin debe poder actualizarse facilmente ante vulnerabilidades de seguridad (considerar GitHub Updater o sistema de updates propio)
- Usar como referencia las WordPress Agent Skills (https://github.com/WordPress/agent-skills) para asegurar patrones modernos y seguros durante el desarrollo

---

## Metricas de rendimiento esperadas

| Metrica | Objetivo |
|---------|----------|
| Lighthouse Performance | > 90 con banners activos |
| CLS (Cumulative Layout Shift) | < 0.1 — reservar espacio para banners |
| Tiempo de carga de banners | < 200ms con lazy loading |
| Queries a BD por pagina | Maximo 2 queries para resolver anuncios activos por zona |

---

## Plan de testing

Pendiente de definir en la investigacion. Linea base:
- Tests unitarios PHP (PHPUnit) para logica de seleccion de anuncios y caducidad
- Tests de integracion para shortcodes (verificar HTML renderizado)
- Test manual de responsive en dispositivos reales

---

## Entregables

- Plugin WordPress empaquetado como .zip instalable
- Documentacion de shortcodes disponibles y parametros
- Guia de uso para el editor (no tecnico)

---

## Notas del briefing

- El sistema actual funciona en zaragoza-ciudad.com con JetEngine CCT + Bricks Builder
- El JSON de Bricks con la maquetacion actual fue proporcionado como referencia
- El editor del blog es periodista veterano — la UX del admin debe ser muy clara
- El usuario esta abierto a funcionalidades adicionales que surjan de la investigacion
- Stack definitivo pendiente de la fase de investigacion
- Usar WordPress Agent Skills (github.com/WordPress/agent-skills) como referencia durante el desarrollo: wp-plugin-development, wp-rest-api, wp-performance, wp-abilities-api
- El plugin debe ser actualizable ante vulnerabilidades futuras

_Actualizado: 2026-03-23_
