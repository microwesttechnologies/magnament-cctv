# Implementation Plan: Expand Content Layout to Full Available Width

Branch: feature/autonomous-uiux-transformation
Created: 2026-08-17

## Original Request

AUDITORÍA Y CORRECCIÓN GLOBAL — CONTENT WIDTH / LAYOUT MANAGEMENT CCTV. Eliminar el espacio horizontal desperdiciado. Corregir la arquitectura del layout (no hacks por pantalla). Content = Viewport − Sidebar. Full width con margen interno.

## Settings
- Testing: yes
- Logging: standard
- Docs: no

## Roadmap Linkage
Milestone: "none"
Rationale: Corrección de layout UX/UI; no hay hito de roadmap específico.

## Causa raíz

En `resources/views/components/layout.blade.php`, el `<main>` combina:

- `max-w-[90rem]` (1440px)
- `mx-auto` (centrado; el `mr-auto` residual empuja el vacío a la derecha)
- `lg:ml-[var(--sidebar-width)]`

En viewports 1366–2560 el contenido queda capado a 1440px y el resto aparece como vacío a la derecha. No es un max-width de página concreta: es el app shell.

## Tasks

### Phase 1: Shell arquitectónico
- [x] Task 1: Definir `.app-main` en `resources/css/app.css` (width auto, max-width none, min-w-0, margin-left sidebar en lg, paddings internos). Logging: N/A (CSS).
- [x] Task 2: Aplicar `.app-main` en `layout.blade.php` y quitar `max-w-[90rem]` + `mx-auto`. Logging: N/A.

### Phase 2: Densidad profesional (UI/UX Pro Max)
- [x] Task 3: Dashboard KPIs 4→2→1; sección 2/3+1/3 desde lg; actividad full width. Archivo: `dashboard.blade.php`.
- [x] Task 4: Tablas fluidas (`.ui-table` 100%, columnas proporcionales) en Proyectos, Cotizaciones, Personal. Filtros de Personal/Trazabilidad a ancho disponible.
- [x] Task 5: Quitar `max-w-4xl` del formulario de cotizaciones (workflow comercial). Conservar max-width en login, settings, modales, toasts.

### Phase 3: Verificación
- [x] Task 6: Vite build + PHPUnit. Confirmar que no queda max-width global en el shell.
