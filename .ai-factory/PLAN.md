# Implementation Plan: Órdenes de servicio + PWA técnicos

Branch: main
Created: 2026-08-17

## Original Request

Evolucionar Soportes a un sistema de Órdenes de Servicio con panel admin/supervisor y PWA exclusiva para técnicos (asignación, reasignación, evidencias PNG, push, trazabilidad). Conservar datos históricos. No romper Proyectos, Cotizaciones, DVR, Cámaras, Personal.

## Settings
- Testing: yes
- Logging: standard
- Docs: no

## Roadmap Linkage
Milestone: none
Rationale: extensión operativa sobre el monolito existente.

## Hallazgos de auditoría

- **Soportes** = `dvr_supports_tb` (hoja de vida del DVR: título, responsable, evidencias). Sin estados, sin PWA, sin asignación formal. Se **conservan**; se **copian** a órdenes de servicio resueltas (`source_dvr_support_id`).
- **Órdenes de instalación** = `installation_orders_tb` desde cotización aprobada (`ORD-YYYY-NNNN`). **No se reutilizan** como órdenes de campo (dominio distinto). Prefijo de servicio: `OS-YYYY-NNNN`.
- **Personal** = `staff_tb` (rol `tecnico|supervisor`, `document_number`, `email`). No está ligado a `users_tb`.
- **Auth** = sesión email+password en `users_tb`. Sin Policies. Sin push. Sin PWA.
- **Trazabilidad** = `traceability_events_tb` (project/quotation/installation order). Se extiende con `service_order_id`.
- Storage evidencias DVR: disco `public` / `dvr_support_evidences`. Órdenes usarán `service_order_evidences`.

## Decisiones

- Nueva entidad `ServiceOrder` (DDD parcial: estados + invariante PNG).
- Login técnico: email + cédula contra Staff (identidad HR ya persistida); User vinculado `staff.user_id`; cédula **no** se usa como password hasheada.
- Rol `users.role = tecnico` vs oficina (`user`/`admin`). Middleware `office` / `technician`.
- PWA en `/tecnico`; restricción móvil por User-Agent (UX, no seguridad).
- Push: suscripciones por usuario + puerto `PushNotifier`; VAPID en `.env`. Tests con fake.
- Evidencia: solo PNG real (finfo/getimagesize); captura móvil convertida a PNG en cliente.

## Tasks

- [x] Task 1: Schema, domain, policies, use cases, migración de soportes
- [x] Task 2: Panel admin y pestaña de proyecto
- [x] Task 3: PWA técnico, login, evidencias, SW, push
- [x] Task 4: Tests + Vite + PHPUnit + verify + commit
