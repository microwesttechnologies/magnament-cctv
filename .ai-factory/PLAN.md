# Implementation Plan: Planos interactivos del proyecto

Branch: main
Created: 2026-08-17

## Original Request

Implementar dentro de PROYECTOS → ABRIR PROYECTO → PLANOS un submódulo completo para administrar planos 2D del proyecto y ubicar visualmente las cámaras CCTV sobre dichos planos. Múltiples planos, visor 2D, click para agregar cámara, DVR/canal del proyecto, canales ya ubicados deshabilitados, unicidad proyecto+DVR+canal en el mismo plano, coordenadas 0.0–1.0, drag & drop, quitar del plano sin borrar la cámara, foto, zoom/pan, Magnament Ops UI v1.1, tests, commit `feat(projects): add interactive project floor plans`, sin push.

## Settings
- Testing: yes
- Logging: standard
- Docs: no

## Roadmap Linkage
Milestone: none
Rationale: no hay hito de planos en ROADMAP.md; el trabajo extiende el módulo de proyectos ya operativo.

## Hallazgos de auditoría

No crear `project_plans` ni `project_plan_cameras`. El producto ya tiene:

- `floor_plans_tb` / `FloorPlan` (nombre, path, sort_order)
- `cameras_tb` / `ProjectCamera` (cámara de proyecto = DVR + canal + posición en plano)
- Unique global `(dvr_id, channel)`
- `pos_x`/`pos_y` hoy en 0–100 (porcentaje CSS)
- Domain `Camera` (`cameras`) es otro bounded context (IP); no usarlo
- Auth por middleware; no hay Policies
- Storage `public` (`floor_plans/`, `camera_photos/`)
- Tab actual `plano` («Plano de la Unidad») + Alpine `planViewer`
- Colocar en el plano HOY crea y borrar del plano HOY elimina `cameras_tb`

Decisión: extender entidades existentes. Colocar = upsert/ubicar. Quitar del plano = `floor_plan_id` null (la cámara sigue en Proyecto → Cámaras). Coordenadas normalizadas 0–1. Tab visible: **Planos**.

## Tasks

- [x] Task 1: Migration nueva — description/status en planos; floor_plan_id y pos nullable; nullOnDelete; convertir coords >1 a 0–1
- [x] Task 2: Modelos FloorPlan/ProjectCamera + payload/eager load + cache por proyecto
- [x] Task 3: Rutas/controllers — CRUD plano, reorder, place/upsert, posición PATCH, unplace, unicidad backend
- [x] Task 4: UI Planos (tab, empty states, canales deshabilitados, drag, pan/zoom, foto, motion)
- [x] Task 5: Tests feature + actualizar UnitFloorPlanModuleTest; npm run build; php artisan test
