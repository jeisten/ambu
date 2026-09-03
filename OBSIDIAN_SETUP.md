# 🧠 Ambu-U: Guía de Configuración del Vault Obsidian (Second Brain)

Esta guía explica cómo configurar, abrir y utilizar la base de conocimiento (`Second Brain`) del proyecto **Ambu-U** ubicada en la carpeta `docs/brain/` mediante **Obsidian**, así como el protocolo de sincronización y memoria continua con agentes de Inteligencia Artificial (**Antigravity / Gemini**).

---

## 🚀 1. Cómo abrir `docs/brain` como Vault en Obsidian

1. Descargá e instalá **Obsidian** desde [obsidian.md](https://obsidian.md/) si aún no lo tenés.
2. Abrí Obsidian y en la pantalla de bienvenida seleccioná **"Open folder as vault"** (Abrir carpeta como bóveda).
3. Navegá en tu explorador de archivos hasta la ruta del proyecto y seleccioná la carpeta:
   ```
   d:/proyecto-u/Ambu-u/docs/brain
   ```
   *(o `[ruta-del-repo]/docs/brain`)*.
4. Hacé clic en **Open** (Abrir).
5. La nota de bienvenida y panel principal es `00-Index/Home.md`.

---

## 🗂️ 2. Estructura de Directorios del Vault

El vault está organizado siguiendo una jerarquía modular orientada a la ingeniería de software y arquitectura:

```
docs/brain/
├── 00-Index/                     # Mapas de Contenido (MOCs) y tableros de navegación
│   ├── Home.md                   # Punto de entrada principal y dashboard
│   ├── Architecture-MOC.md       # Diagramas y pilares arquitectónicos
│   └── Roadmap-MOC.md            # Plan de implementación y fases
├── 01-Project-Specs/             # Especificaciones formales del sistema
│   ├── Business-Rules.md         # Reglas de negocio y fórmulas de cálculo
│   ├── Data-Dictionary.md        # Diccionario de datos, tablas y tipos
│   └── API-Contracts.md          # Contratos RESTful y eventos WebSockets
├── 02-ADR/                       # Architectural Decision Records
│   └── ADR-001-laravel-11-and-reverb.md
├── 03-Daily-Logs/                # Bitácoras diarias de ingeniería y sesiones
│   └── 2026-08-29.md             # Log de arranque y configuración inicial
├── 04-Modules/                   # Especificación profunda de módulos
│   ├── Auth-Module.md            # Autenticación con Laravel Sanctum
│   ├── Fleet-Ambulances.md       # Gestión de flota y alertas SOAT
│   ├── Remissions-Tracking.md    # Ciclo de vida y cierre de remisiones
│   └── Telemetry-Haversine.md     # Ingesta GPS, Haversine y Reverb
└── 05-Templates/                 # Plantillas estandarizadas para nuevas notas
    ├── template-adr.md
    ├── template-daily-log.md
    └── template-module.md
```

---

## 📝 3. Convenciones de Notas y Sintaxis

1. **Frontmatter YAML:** Todas las notas deben iniciar con metadatos estructurados para facilitar la búsqueda e indexación:
   ```yaml
   ---
   title: "Título de la Nota"
   type: moc | spec | adr | daily-log | module
   tags:
     - tag1
     - tag2
   status: active | planned | in-progress | approved | accepted
   date: YYYY-MM-DD
   ---
   ```
2. **Enlaces Bidireccionales (WikiLinks):** Utilizá `[[NombreDeLaNota]]` para enlazar conceptos relacionados, habilitando el **Graph View** interactivo de Obsidian.
3. **Diagramas Mermaid:** Utilizá bloques ```mermaid para documentar flujos de datos, máquinas de estado y secuencias.

---

## 🔌 4. Plugins Recomendados para Obsidian

Para maximizar la experiencia técnica en Obsidian, se recomienda habilitar los siguientes **Community Plugins**:

1. **Dataview**: Permite realizar consultas tipo SQL sobre los metadatos YAML de las notas (útil para listar ADRs, módulos por estado y logs).
2. **Omnisearch** o **Quick Switcher++**: Búsqueda ultrarrápida y navegación instantánea por símbolos y encabezados.
3. **Obsidian Git**: Sincronización automática o manual con el repositorio de control de versiones Git.
4. **Mermaid Tools / Excalidraw**: Renderizado interactivo y edición visual de diagramas de arquitectura.

---

## 🤖 5. Integración y Memoria Continua con Antigravity / Gemini

El vault `docs/brain` funciona como la **memoria a largo plazo** persistente para el agente de IA:

1. **Lectura de Contexto:** Antes de iniciar cualquier refactor, nueva funcionalidad o corrección de bugs, el agente consulta `00-Index/Home.md`, `01-Project-Specs/` y los módulos correspondientes en `04-Modules/`.
2. **Registro de Decisiones (ADR):** Cada vez que se adopte una tecnología, patrón de diseño o cambio estructural crítico, el agente creará una nueva nota en `02-ADR/` siguiendo `template-adr.md`.
3. **Bitácoras Diarias (Daily Logs):** Al final de sesiones significativas de desarrollo, el agente o desarrollador actualizará o creará la nota del día en `03-Daily-Logs/YYYY-MM-DD.md` resumiendo cambios, bloqueantes y próximos pasos.
4. **Actualización de Especificaciones:** Cuando cambie una regla de validación o un endpoint de API, el agente sincronizará inmediatamente `Business-Rules.md`, `Data-Dictionary.md` o `API-Contracts.md`.

De esta forma, cualquier sesión futura de IA recupera el estado exacto del proyecto sin pérdida de contexto.
