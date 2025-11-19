Diagrama de Clases y MER

Este proyecto incluye dos scripts para generar diagramas en formato PlantUML:

- `scripts/generate_class_diagram.php` — analiza `app/` y genera `docs/class_diagram.puml`.
- `scripts/generate_mer_diagram.php` — analiza `database/migrations/` y genera `docs/mer_diagram.puml`.

Generar diagrama de clases:

```powershell
php scripts/generate_class_diagram.php
```

Generar diagrama MER:

```powershell
php scripts/generate_mer_diagram.php
```

Los archivos `.puml` resultantes se guardan en `docs/`. Puedes abrirlos con la extensión PlantUML en VS Code o renderizarlos con `plantuml.jar`.

Si no ves los archivos en `docs/`, ejecuta los scripts anteriores y revisa los mensajes en consola.
