# Implementation Plan: Mejora del módulo de Cotizaciones

Branch: main
Created: 2026-08-17

## Original Request

Modificar Cotizaciones: campos Solicitud y Solución diseñada, propuesta económica sin marca/serie, logo de empresa en Configuración y PDF superior derecha. No romper IVA, totales, productos ni numeración.

## Settings
- Testing: yes
- Logging: standard
- Docs: no

## Roadmap Linkage
Milestone: none
Rationale: extensión del módulo comercial existente.

## Hallazgos

- `work_description` (required) es la descripción actual → se reutiliza como **Solicitud** (no se pierde data).
- Nueva columna `designed_solution` (nullable).
- Líneas ya tienen brand/serial: se conservan en BD y formulario; se ocultan en tabla económica y PDF.
- IVA/totales viven en Domain `Quotation::recalculateTotals` — no se tocan.
- Settings ya usa `app_settings_tb` + caché VAT. El logo se guarda igual (`company_logo_path`) en Storage `public`.
- No hay Policies: Configuración está detrás de `auth` (igual que hoy).

## Tasks

- [x] Task 1: Migration `designed_solution` + CompanyIdentity (logo/settings) con caché
- [x] Task 2: Domain/Application/Repo: persistir solicitud + solución sin cambiar cálculos
- [x] Task 3: Formulario, detalle y PDF (estructura, tabla 4 columnas, logo arriba derecha)
- [x] Task 4: Tests + npm run build + php artisan test
