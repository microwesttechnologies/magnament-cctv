# Implementation Plan: Limpieza de cards de servicio en Dashboard

Branch: main
Created: 2026-08-17

## Original Request

FASE — LIMPIEZA DEL DASHBOARD

Necesito eliminar COMPLETAMENTE del Dashboard principal las
siguientes cards de estadísticas:

- SERVICIO PENDIENTES
- SERVICIO ASIGNADAS
- SERVICIO EN PROCESO
- SERVICIO RESUELTAS
- SERVICIO CANCELADAS

IMPORTANTE:

NO eliminar ni modificar el módulo de ÓRDENES.

NO eliminar:

- Órdenes
- Estados de órdenes
- Asignación
- Reasignación
- PWA
- Notificaciones Push
- Técnicos
- Trazabilidad
- Backend de órdenes

ÚNICAMENTE eliminar su representación visual del Dashboard.

## Settings
- Testing: yes
- Logging: standard
- Docs: no

## Roadmap Linkage
Milestone: "none"
Rationale: ROADMAP.md no existe; cambio acotado al Dashboard.

## Audit (evidencia)

| Pieza | Ubicación | Decisión |
|-------|-----------|----------|
| Vista Dashboard | `resources/views/dashboard.blade.php` | Eliminar las 5 `<x-ui.stat-card>` |
| Controller | `app/Http/Controllers/DashboardController.php` | No calcula KPIs; no tocar |
| Consultas exclusivas | `app/Support/Cache/DashboardSnapshot.php` | Eliminar `ServiceOrder` groupBy + keys `service_orders_*` |
| Consumidores de esas keys | Solo dashboard view + snapshot | Seguro eliminar |
| Módulo Órdenes | `ServiceOrderController`, `resources/views/service-orders/*`, PWA técnico | NO tocar (cards de estado viven en `/ordenes`) |
| Cache invalidation | `CacheInvalidator::forModel(ServiceOrder)` | Conservar: actividad reciente del Dashboard sí consume eventos de trazabilidad de órdenes |

Layout post-cambio: 4 KPIs restantes (`Proyectos activos`, `En instalación`, `Cotizaciones pendientes`, `Órdenes abiertas`) en `grid-cols-1 sm:grid-cols-2 lg:grid-cols-4` — ocupa todo el ancho, sin celdas vacías. Encabezado, acciones rápidas y actividad reciente intactos.

## Tasks

### Phase 1: Vista y snapshot
- [x] Task 1: Quitar las 5 cards de servicio del Dashboard y dejar el grid de 4 KPIs a ancho completo (`resources/views/dashboard.blade.php`). Logging: no aplica (Blade).
- [x] Task 2: Eliminar consulta `ServiceOrder` y keys `service_orders_*` exclusivas de `DashboardSnapshot`; conservar proyectos, cotizaciones, órdenes de instalación, atención y actividad (`app/Support/Cache/DashboardSnapshot.php`). Logging: `Log::debug` al reconstruir snapshot sin KPIs de servicio.

### Phase 2: Tests y verificación
- [x] Task 3: Asegurar que el test de Dashboard comprueba ausencia de las 5 labels y presencia del resto de bloques (`tests/Feature/PerformanceOptimizationTest.php`). Logging: no aplica (PHPUnit).
- [x] Task 4: Ejecutar `npm run build` y `php artisan test`; corregir regresiones sin tocar el módulo de Órdenes.

## Commit Plan
Un solo commit al final: `refactor(dashboard): remove service status cards`
