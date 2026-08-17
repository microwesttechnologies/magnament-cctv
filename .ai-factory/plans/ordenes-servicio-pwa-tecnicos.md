# Implementation Plan: Órdenes de servicio + PWA técnicos

Branch: main
Created: 2026-08-17

## Original Request

FASE — SISTEMA DE ÓRDENES DE SERVICIO + PWA TÉCNICOS MANAGEMENT CCTV (auditoría primero; evolucionar Soportes a Órdenes; panel admin + PWA; estados; asignación/reasignación; evidencias PNG; push; tests; commits; no push remoto).

## Settings
- Testing: yes
- Logging: verbose
- Docs: no

## Roadmap Linkage
Milestone: "none"
Rationale: ROADMAP.md no existe en el repositorio.

## Audit (evidencia — no duplicar)

El sistema **ya está implementado** en `feat(orders): implement technician service orders PWA` (`f0ac3cf9`) y pulido PWA (`53d88b96`). No crear un segundo módulo.

| Concepto | Hallazgo | Reutilizar |
|----------|----------|------------|
| Soportes | `dvr_supports_tb` = hoja de vida DVR (título, responsable, evidencias). Sigue vivo en Proyecto → DVR → Consultar. | Conservar. No borrar datos. |
| Migración histórica | `2026_08_17_000004` copia soportes a `service_orders_tb` `resuelta` con `source_dvr_support_id`. | Ya aplicada. No repetir. |
| Órdenes de servicio | `ServiceOrder` + `ServiceOrderWorkflow` + `ServiceOrderPolicy` + UI `/ordenes` + PWA `/tecnico` | Extender, no reescribir |
| Órdenes de instalación | `InstallationOrder` / código `ORD-` (cotización) | Dominio distinto. Prefijo de servicio: `OS-` |
| Personal / login | `Staff.document_number` + email; `TechnicianAccountProvisioner` crea `User.role=tecnico` con password aleatorio. Cédula no es hash de login; es dato de ficha. | Reutilizar |
| Trazabilidad | `TraceabilityRecorderInterface` + `service_order.*` | Reutilizar |
| Storage | disco `public` / `service_order_evidences` | Reutilizar |
| Auth | Un solo `User`; middleware `office` / `technician` / `technician.mobile` | Reutilizar |

### Estados actuales (máquina válida)

`pendiente` → `asignada` → `en_proceso` → `resuelta` | `cancelada`

Reasignación: `asignada|en_proceso` → `asignada` (técnico nuevo). PNG obligatorio solo `en_proceso` → cierre.

### Gaps a cerrar (esta ejecución)

1. Dashboard: restaurar KPIs reales de órdenes de servicio (unstaged anterior los eliminaba).
2. Web Push real: hoy `DatabasePushNotifier` solo inserta inbox; no envía al Service Worker.
3. Permiso de notificaciones en PWA (opt-in, sin nag).
4. Marcar avisos como leídos + badge.
5. PWA assets fuera de `public/tecnico/` (Apache 403 del start_url).
6. MIME persistido desde detección real, no hardcoded.
7. Historial de trazabilidad en detalle admin.
8. Tests de push, leídas, KPIs, transiciones y PWA.

## Commit Plan
- **Commit 1** (PWA shell): servir manifest/SW/offline fuera de `public/tecnico/`
- **Commit 2** (push + inbox + dashboard KPIs + tests): `feat(orders): send technician web push and restore order KPIs`

El commit principal `feat(orders): implement technician service orders PWA` ya existe en HEAD.

## Tasks

### Phase 1: Conservar PWA shell
- [x] Task 1: Completar `TechnicianPwaController`, assets en `public/pwa/`, rewrite Apache, HTTPS/proxies, tests de manifest/SW. Logging: WARN si falta archivo SW.

### Phase 2: Push, inbox, dashboard
- [x] Task 2: Puerto `WebPushDispatcherInterface` + Minishlink; VAPID desde `.env`; skip si no hay claves. Logging: INFO envío, WARN sin VAPID, ERROR fallo 410 (borrar suscripción).
- [x] Task 3: Permiso push en Perfil (opt-in); marcar `read_at`; badge en nav. Logging: DEBUG permiso/subscribe.
- [x] Task 4: Restaurar KPIs Dashboard (conteos reales `service_orders_tb`) con labels Órdenes pendientes/asignadas/en proceso/resueltas/canceladas. Logging: DEBUG snapshot.
- [x] Task 5: MIME real al guardar evidencia; historial `TraceabilityEvent` en show admin.

### Phase 3: Tests y verify
- [x] Task 6: Tests PHPUnit (push dispatcher, leídas, KPIs, PNG, transiciones, PWA) + `npm run build` + `php artisan test`.
