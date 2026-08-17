# AGENTS.md

> Mapa estructural para agentes de IA. Mantener alineado con el código real; no describir una arquitectura ideal inventada.

## Resumen del proyecto

CCTV Manager (Magnament): monolito Laravel operativo para proyectos CCTV. AI Factory documenta el sistema para **extenderlo** (Cotizaciones → Orden → Trazabilidad) sin reemplazar módulos existentes. BD real: **MySQL**.

## Stack tecnológico

- **Lenguaje:** PHP 8.2
- **Framework:** Laravel 12
- **Base de datos:** MySQL (Eloquent)
- **Frontend:** Blade, Alpine.js, Vite, Tailwind CSS 4
- **Tests:** PHPUnit

## Estructura del proyecto

```
app/
  Application/Camera/     # Use cases del contexto Camera
  Application/Quotation/  # Use cases de cotizaciones
  Application/Order/      # Conversión a Orden de Instalación
  Domain/Camera/          # Entidades, VOs, puertos Camera
  Domain/Quotation/       # Dominio cotizaciones (estados, IVA, totales)
  Domain/Order/           # Puerto repositorio de órdenes
  Infrastructure/         # Eloquent, PDF Spatie, audit, trazabilidad, VAT settings
  Http/Controllers/       # Web: Projects, Quotation, Traceability, DVR, Staff, Auth…
  Models/                 # Eloquent de negocio (Project, Quotation, InstallationOrder…)
  Providers/              # Incl. RepositoryServiceProvider
bootstrap/                # Arranque Laravel 12
config/                   # Configuración (database mysql disponible)
database/migrations/      # Esquema (tablas *_tb de negocio)
resources/views/          # Blade (layout, sidebar, proyectos, staff…)
routes/web.php            # Rutas autenticadas principales
routes/api.php            # API cámaras
.ai-factory/              # Contexto AI Factory (DESCRIPTION, ARCHITECTURE, rules)
.agents/skills/           # Skills instaladas / generadas para agentes
```

## Puntos de entrada clave

| Archivo | Propósito |
|---------|-----------|
| `artisan` | CLI Laravel |
| `bootstrap/app.php` | Routing web/api y middleware |
| `routes/web.php` | App autenticada (proyectos, personal, settings) |
| `routes/api.php` | Endpoints de cámaras |
| `app/Providers/RepositoryServiceProvider.php` | Bindings de repositorios Domain |
| `.ai-factory/DESCRIPTION.md` | Especificación de producto y alcance Cotizaciones |
| `.ai-factory/ARCHITECTURE.md` | Guías de arquitectura adaptadas al código actual |

## Documentación

| Documento | Ruta | Descripción |
|-----------|------|-------------|
| README | README.md | README del skeleton Laravel |
| Descripción AI Factory | `.ai-factory/DESCRIPTION.md` | Stack, módulos vivos, alcance Cotizaciones |
| Arquitectura | `.ai-factory/ARCHITECTURE.md` | Patrón y reglas de extensión |
| Reglas base | `.ai-factory/rules/base.md` | Convenciones detectadas |

## Archivos de contexto para IA

| Archivo | Propósito |
|---------|-----------|
| AGENTS.md | Mapa del repositorio para agentes |
| `.ai-factory/DESCRIPTION.md` | Qué es el producto y qué se va a extender |
| `.ai-factory/ARCHITECTURE.md` | Cómo organizar código nuevo vs existente |
| `.ai-factory/config.yaml` | Idioma, git, paths de AI Factory |

## Reglas para agentes

- Descomponer comandos de shell: no encadenar con `&&` cuando se pueda evitar
  - Incorrecto: `git checkout 13.x && git pull`
  - Correcto: primero `git checkout 13.x`, luego `git pull origin 13.x`
- BD de diseño/implementación: **MySQL** (no SQLite, no PostgreSQL)
- Conservar funcionalidades existentes; extender Cotizaciones sin greenfield
- Skills de dominio: `magnament-cctv-domain`, `magnament-quotations`, `laravel-ddd-magnament`
