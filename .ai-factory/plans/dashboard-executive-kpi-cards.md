# Implementation Plan: Dashboard ejecutivo — 4 KPIs

Branch: main
Created: 2026-08-17

## Original Request

Simplificar el Dashboard principal dejando únicamente 4 métricas ejecutivas:
Proyectos activos, En instalación, Cotizaciones pendientes, Órdenes abiertas.

Eliminar del Dashboard (no del módulo Órdenes) las 5 cards por estado de servicio.

## Audit

| Pieza | Ubicación | Decisión |
|-------|-----------|----------|
| Vista Dashboard | `resources/views/dashboard.blade.php` | Mantener grid 4 cols; eliminar segunda fila de 5 cards |
| Snapshot | `app/Support/Cache/DashboardSnapshot.php` | Quitar keys `service_orders_*`; `orders_open` vía `ServiceOrderStatus::isActive()` |
| Controller | `app/Http/Controllers/DashboardController.php` | Sin cambios (delega en snapshot) |
| Módulo Órdenes | `ServiceOrderController`, `service-orders/index.blade.php` | NO tocar |
| Dominio | `ServiceOrderStatus::isActive()` | Pendiente + Asignada + En proceso |

## Tasks

### Phase 1: Vista y snapshot
- [x] Task 1: Una sola fila de 4 KPIs responsive en `dashboard.blade.php`
- [x] Task 2: Consultas mínimas en `DashboardSnapshot.php`; `orders_open` alineado al dominio

### Phase 2: Tests y verificación
- [x] Task 3: Actualizar `PerformanceOptimizationTest` (presencia 4 KPIs, ausencia 5 labels)
- [x] Task 4: `npm run build`, `php artisan test`, aif-verify

## Commit Plan

`refactor(dashboard): simplify executive KPI cards`
