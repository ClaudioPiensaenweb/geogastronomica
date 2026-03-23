# Investigacion: WordPress Agent Skills para desarrollo de plugins

> Proyecto: GeoGastronomica | Area: patrones | Prioridad: alta

## Resumen ejecutivo

WordPress/agent-skills es un repositorio oficial de Automattic/WordPress con 13 skills portables (instrucciones, checklists y scripts) que dotan a Claude Code de conocimiento experto sobre desarrollo WordPress. Para GeoGastronomica, las skills relevantes son `wp-plugin-development` y `wp-performance`, que cubren exactamente los patrones del CPT, meta boxes, shortcodes, seguridad y optimizacion de queries que necesita el plugin. Se instalan globalmente con un comando npx y Claude las aplica automaticamente al desarrollar.

## Hallazgos clave

1. **Instalacion trivial para Claude Code**: Un solo comando instala las skills en `~/.claude/skills/` donde Claude Code las descubre automaticamente. No requiere configuracion adicional en el proyecto.
   ```
   npx skills add https://github.com/WordPress/agent-skills --agent claude-code --skill wp-plugin-development
   npx skills add https://github.com/WordPress/agent-skills --agent claude-code --skill wp-performance
   ```

2. **wp-plugin-development cubre todos los patrones del proyecto**: Arquitectura (un unico bootstrap, hooks diferidos, admin detras de `is_admin()`), ciclo de vida (activation/deactivation/uninstall.php), seguridad no negociable (nonces + capability checks en todo formulario, `$wpdb->prepare()`, escape contextual de output), y un checklist de verificacion concreto.

3. **wp-performance cubre el requisito de rendimiento**: Estrategias de object cache, deteccion de patrones N+1 en queries, y guidance sobre lazy loading. Directamente aplicable al requisito de maximo 2 queries por zona de anuncio y CLS < 0.1.

4. **Estructura de cada skill**: Cada skill tiene un `SKILL.md` principal con patrones y un directorio `references/` con documentacion profunda por topico (security.md, lifecycle.md, data-and-cron.md, settings-api.md, debugging.md). Esto significa que Claude puede consultar el detalle tecnico sin necesidad de buscar en internet durante el desarrollo.

5. **Skills no relevantes para este proyecto**: `wp-block-development`, `wp-block-themes`, `wp-interactivity-api`, `wpds` — el plugin usa shortcodes PHP nativo, no bloques Gutenberg. No instalar estas skills para mantener el contexto limpio.

6. **wp-wpcli-and-ops es util secundariamente**: Para tareas de mantenimiento, testing headless y automatizacion de deploy. Instalacion opcional tras tener el plugin funcionando.

## Recomendacion

Instalar `wp-plugin-development` y `wp-performance` antes de comenzar el desarrollo. Verificar la instalacion con `node shared/scripts/skillpack-install.mjs --list`. Durante el desarrollo, mencionar a Claude que aplique las skills en cada tarea critica (registro de CPT, formularios de admin, queries de banners activos). No instalar el resto de skills — el proyecto no usa bloques Gutenberg ni temas de bloques.

**Checklist de verificacion obligatorio** (extraido de wp-plugin-development):
- [ ] Plugin activa sin fatals ni notices
- [ ] Settings persisten y se recuperan correctamente
- [ ] Capability + nonce enforced en todos los formularios del admin
- [ ] Uninstall elimina solo los datos propios del plugin
- [ ] PHPCS pasa sin errores (WordPress Coding Standards)
- [ ] PHPUnit tests pasan (logica de seleccion de anuncios y caducidad)

## Alternativas consideradas

| Opcion | Pros | Contras |
|--------|------|---------|
| WordPress/agent-skills (oficial) | Mantenido por Automattic/WordPress, actualizado con WP 6.x, cubre exactamente plugin dev + performance | Requiere Node.js para instalar |
| elvismdev/claude-wordpress-skills (tercero) | Incluye security auditing y performance optimization | Menos oficial, mantenimiento incierto |
| Sin skills (solo CLAUDE.md del proyecto) | Sin dependencias externas | Claude sin contexto experto de WordPress, mayor riesgo de patrones incorrectos |
| Instalar todas las 13 skills | Cobertura maxima | Contamina el contexto con skills irrelevantes (block themes, interactivity API) que pueden confundir las respuestas |

## Referencias

- [WordPress/agent-skills en GitHub](https://github.com/WordPress/agent-skills)
- [WordPress Agent Skills — WordPress.com Developer Docs](https://developer.wordpress.com/docs/agent-skills/)
- [wp-performance SKILL.md](https://github.com/WordPress/agent-skills/blob/trunk/skills/wp-performance/SKILL.md)
- [wp-plugin-development SKILL.md](https://github.com/WordPress/agent-skills/blob/trunk/skills/wp-plugin-development/SKILL.md)
- [Build WordPress Plugins with AI: Claude Code + WordPress Studio](https://wordpress.com/blog/2026/02/12/build-wordpress-plugins-with-ai-claude-code/)
