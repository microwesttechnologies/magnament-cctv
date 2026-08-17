# Restaurar Plano de la Unidad

**Fecha:** 2026-08-17
**Rama:** main
**Testing:** sí
**Logging:** estándar
**Docs:** no

## Original Request

FASE 9 — Restauración funcional del módulo "Plano de la Unidad" sin revertir UX/UI.

## Hallazgos

El módulo **no fue eliminado del backend**. Sigue en:

- Rutas: `projects.floor-plans.store|destroy`, `projects.cameras.*`
- `ProjectController`, `ProjectCameraController`
- Modelos `FloorPlan`, `ProjectCamera`, `Dvr`
- Vista `resources/views/projects/show.blade.php` + Alpine `planViewer`

**Regresión:** `71dd0ea6` (`feat(ui): redesign commercial workflows`) metió el visor en una pestaña `cctv` (default `resumen`). El plano dejó de verse al abrir el proyecto. Tras CRUD de cámaras, `open_plan_viewer` no abre el visor porque el padre está `x-show` oculto. El texto «Plano de la Unidad» nunca existió en git; el original se llamaba «Topología y Distribución» y estaba siempre visible.

## Tareas

- [x] Restaurar pestaña/nombre «Plano de la Unidad», preview en Resumen, URL `?tab=plano`
- [x] Redirigir CRUD de hojas/cámaras a `tab=plano` y abrir visor
- [x] Integrar tokens Magnament Ops, motion sutil, touch 44px
- [x] Tests de ruta, auth, CRUD y permisos
- [x] `npm run build` + `php artisan test`
