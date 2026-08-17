# Plan de implementación: Módulo de Cotizaciones

Branch: none (detached HEAD; `git.create_branches: false` — sin crear rama)
Created: 2026-08-16

## Original Request

Implementar el módulo completo de Cotizaciones para el sistema CCTV Manager existente.

Flujo:
Proyecto → Cotización → PDF → Aprobación → Orden de Instalación/Implementación → Trazabilidad → Historial.

Analiza primero el código existente y genera un plan detallado de implementación.
No implementes todavía.

## Settings

- Testing: no
- Logging: standard
- Docs: no

## Constraints (confirmadas)

- BD real: **MySQL** (no SQLite, no PostgreSQL)
- No implementar en esta fase: solo plan
- IVA configurable y dinámico; **sin hardcode** en vistas/código de negocio
- PDF con **spatie/laravel-pdf** (skill `laravel-pdf` ya instalada)
- Integrar Cotizaciones, Órdenes, Trazabilidad e Historial **sin romper** auth, proyectos, planos, DVRs, cámaras, personal, settings
- Cotización **vinculada a Proyecto**; conversión a Orden **solo** cuando el estado lo permita (aprobada)
- Conservar arquitectura híbrida, UI Blade/Alpine/layout/sidebar y patrones existentes
- PDF formato genérico profesional y reutilizable (plantilla Blade compartida)
- Cálculos monetarios precisos; **persistir el % de IVA aplicado** en el historial de la cotización (snapshot)

## Commit Plan

- **Commit 1** (tras tareas 1–3): `feat(cotizaciones): add MySQL schema and Eloquent models`
- **Commit 2** (tras tareas 4–6): `feat(cotizaciones): add domain use cases for quotes and totals`
- **Commit 3** (tras tareas 7–9): `feat(cotizaciones): wire persistence, PDF adapter and DI`
- **Commit 4** (tras tareas 10–12): `feat(cotizaciones): add web UI, routes and sidebar`
- **Commit 5** (tras tareas 13–15): `feat(cotizaciones): add order conversion, traceability and audit`

## Contexto del código existente (síntesis)

- **Proyectos:** Eloquent `Project` (`projects_tb`) + `ProjectController` + vistas Blade; hijos anidados (DVRs, planos, cámaras) con ownership `abort_unless`.
- **DDD parcial Camera:** `Domain` / `Application` / `Infrastructure` + `RepositoryServiceProvider` — plantilla de capas para reglas ricas.
- **Settings:** solo perfil de usuario; **no hay IVA** aún.
- **Sidebar:** placeholders `cotizaciones`, `trazabilidad`, `facturacion` sin rutas en `web.php`.
- **Composer:** sin paquete PDF aún; hay que añadir `spatie/laravel-pdf` (+ driver DomPDF o Browsershot según entorno Windows).
- **Trazabilidad / historial / órdenes:** no existen en `app/`; hay que crearlos de forma aditiva.

## Decisiones de diseño (plan)

| Tema | Decisión |
|------|----------|
| Persistencia | MySQL, tablas `*_tb`, FKs a `projects_tb` |
| Capas Cotización/Orden | DDD parcial (como Camera) para estados, totales, conversión; Eloquent models en Infrastructure/Models |
| UI | Blade + Alpine + `x-layout` / `x-sidebar`; listado global + anidado en proyecto |
| IVA | Tabla/config de aplicación editable (p. ej. desde Configuración o settings de app); al calcular/guardar cotización se copia `vat_rate_percent` en la cabecera |
| Dinero | enteros en centavos **o** `decimal(12,2)` con BCMath/brick — preferir **decimal(14,2)** + recálculo server-side consistente; evitar float PHP |
| Estados cotización | `borrador` → `emitida` → `aprobada` \| `rechazada`; desde `aprobada` → `convertida` (tras crear Orden). Cancelación desde borrador/emitida |
| Orden | Solo desde cotización `aprobada`; una cotización → como máximo una Orden (unique `quotation_id`) |
| PDF | Vista Blade genérica `pdf.layouts.professional` + vista `pdf.quotations.show`; descarga auth |
| Trazabilidad | Módulo mínimo + eventos enlazables a Proyecto/Cotización/Orden; no reemplazar soportes DVR |
| Historial | Tabla de auditoría append-only de cambios importantes |

## Tasks

### Fase 1: Esquema MySQL y modelos Eloquent

- [ ] Task 1: Migraciones MySQL del módulo comercial
  - Crear migraciones Eloquent compatibles con **MySQL** (no sqlite-specific):
    - `app_settings_tb` (o `vat_settings_tb`): clave/valor o fila de IVA por defecto (`vat_rate_percent` decimal, activo)
    - `quotations_tb`: `project_id` FK cascade/restrict, código único, `work_description`, `status`, `vat_rate_percent` (snapshot), `subtotal`, `vat_amount`, `total`, timestamps, `created_by` nullable
    - `quotation_lines_tb`: `quotation_id`, `product_name`, `quantity`, `brand`, `serial`, `unit_price`, `line_subtotal`, `sort_order`
    - `installation_orders_tb`: `project_id`, `quotation_id` unique, `code`, `status`, `notes`, timestamps
    - `traceability_events_tb`: `project_id`, `quotation_id` nullable, `order_id` nullable, `event_type`, `title`, `payload` JSON, `user_id` nullable, timestamps
    - `audit_logs_tb`: `auditable_type`, `auditable_id`, `action`, `old_values` JSON, `new_values` JSON, `user_id`, timestamps
  - LOGGING (standard): al correr migrate en implement, no requiere log app; documentar en comentario de migración el propósito.
  - Files: `database/migrations/2026_08_17_000001_create_quotations_module_tables.php` (o archivos separados por tabla)

- [ ] Task 2: Modelos Eloquent y relaciones en Project
  - Crear `App\Models\Quotation`, `QuotationLine`, `InstallationOrder`, `TraceabilityEvent`, `AuditLog`, `AppSetting` (nombres finales coherentes con tablas `*_tb`).
  - Añadir `Project::quotations()` y, si aplica, `installationOrders()` / `traceabilityEvents()` **sin** alterar relaciones existentes (`dvrs`, `floorPlans`, `projectCameras`).
  - Casts decimal/string para montos y `vat_rate_percent`; evitar float.
  - LOGGING: N/A en modelos; casts y fillable explícitos.
  - Files: `app/Models/Quotation.php`, `QuotationLine.php`, `InstallationOrder.php`, `TraceabilityEvent.php`, `AuditLog.php`, `AppSetting.php`, `app/Models/Project.php` (solo relaciones nuevas)

- [ ] Task 3: Semilla/config inicial de IVA y dependencia PDF
  - Añadir `spatie/laravel-pdf` vía Composer (driver recomendado para Windows local: DomPDF si Browsershot/Chrome no está disponible; documentar en comentario README interno del plan de implement).
  - Seed o migración de datos: IVA por defecto configurable (p. ej. 16.00) en `app_settings_tb` — **valor en BD, no en código hardcodeado de cálculos**.
  - LOGGING: `Log::info` al publicar setting inicial solo en seeder si aplica.
  - Files: `composer.json`, `database/seeders/...`, posiblemente `config/laravel-pdf.php` publicado

<!-- Commit checkpoint: tasks 1-3 -->

### Fase 2: Dominio y casos de uso

- [ ] Task 4: Dominio Cotización (entidad, VOs, estados)
  - Crear bounded context bajo `app/Domain/Quotation/`:
    - Enum `QuotationStatus` con transiciones permitidas
    - VOs: `Money` (o `DecimalAmount`), `VatRate`, `QuotationId`, `ProjectId`
    - Entidad/agregado `Quotation` con líneas; métodos `recalculateTotals(VatRate $rate)` que **fija** `vatRatePercent` snapshot + subtotal/iva/total
    - Reglas: no convertir si status ≠ `aprobada`; líneas con cantidad > 0; precios ≥ 0
  - LOGGING: no en Domain puro; excepciones de dominio claras (`InvalidQuotationTransition`, etc.)
  - Files: `app/Domain/Quotation/**`

- [ ] Task 5: Puerto de repositorio y use cases de cotización
  - `QuotationRepositoryInterface` + use cases Application:
    - `CreateQuotationUseCase`
    - `UpdateQuotationLinesUseCase`
    - `RecalculateQuotationTotalsUseCase` (lee IVA vigente de settings **o** conserva snapshot según política: en borrador puede refrescar IVA vigente; al emitir/aprobar **congela** `vat_rate_percent`)
    - `TransitionQuotationStatusUseCase` (borrador→emitida→aprobada/rechazada; cancelar)
  - DTOs sin `Request` HTTP.
  - LOGGING (standard): en cada use case `Log::info` al inicio/éxito con `quotation_id`, `project_id`, `status`; `Log::warning` en transición inválida; `Log::error` en fallos inesperados. Formato: `[Quotation.UseCaseName] message {context}`.
  - Files: `app/Application/Quotation/**`, `app/Domain/Quotation/Repositories/QuotationRepositoryInterface.php`

- [ ] Task 6: Use cases Orden, Trazabilidad y Auditoría (puertos)
  - `ConvertApprovedQuotationToOrderUseCase`: valida `aprobada`, crea Orden, marca cotización `convertida`, escribe eventos.
  - Puertos: `AuditLoggerInterface`, `TraceabilityRecorderInterface`, `QuotationPdfGeneratorInterface`.
  - LOGGING (standard): INFO conversión exitosa; WARN intento ilegal; ERROR rollback.
  - Files: `app/Application/Quotation/**`, `app/Application/Order/**` (o mismo contexto), `app/Domain/**/Ports` o interfaces en Domain

<!-- Commit checkpoint: tasks 4-6 -->

### Fase 3: Infraestructura (Eloquent, PDF, settings)

- [ ] Task 7: Repositorios Eloquent y bindings DI
  - Implementar `EloquentQuotationRepository` (+ mappers entity↔model) siguiendo `EloquentCameraRepository`.
  - Registrar bindings en `RepositoryServiceProvider` (no tocar bindings Camera).
  - LOGGING: DEBUG opcional en mappeo solo si LOG_LEVEL lo permite; INFO en save fallido → ERROR.
  - Files: `app/Infrastructure/Persistence/Eloquent/**`, `app/Providers/RepositoryServiceProvider.php`

- [ ] Task 8: Adapter PDF profesional reutilizable
  - Implementar `QuotationPdfGenerator` con `Spatie\LaravelPdf\Facades\Pdf`.
  - Layout genérico: `resources/views/pdf/layouts/professional.blade.php` (membrete genérico, tipografía clara, márgenes, tabla, totales).
  - Contenido: `resources/views/pdf/quotations/show.blade.php` usando el layout.
  - Guardar/descargar vía disco `local`/`public` o stream download; ruta autenticada.
  - LOGGING: INFO generación OK (`quotation_id`); ERROR fallo driver PDF.
  - Files: `app/Infrastructure/Pdf/**`, `resources/views/pdf/**`

- [ ] Task 9: Servicio de IVA configurable + auditoría/trazabilidad Eloquent
  - Leer/actualizar tasa IVA desde `AppSetting` (UI en Task 11).
  - Implementar `EloquentAuditLogger` y `EloquentTraceabilityRecorder`.
  - Eventos mínimos de trazabilidad: `quotation.created`, `quotation.approved`, `quotation.converted`, `order.created` (extensible).
  - LOGGING: INFO al registrar evento de trazabilidad; WARN si falta project_id.
  - Files: `app/Infrastructure/Settings/**` o Application service, `app/Infrastructure/Audit/**`, `app/Infrastructure/Traceability/**`

<!-- Commit checkpoint: tasks 7-9 -->

### Fase 4: HTTP, UI y navegación

- [ ] Task 10: Rutas y controladores web
  - Registrar bajo `middleware('auth')` en `routes/web.php` **sin eliminar** rutas existentes:
    - `GET /cotizaciones` → `cotizaciones` (index global)
    - Nested: `projects/{project}/cotizaciones` CRUD/show
    - Acciones: emitir, aprobar, rechazar, PDF download, convertir a orden
    - `GET /trazabilidad` → listado/filtro por proyecto
    - Rutas de órdenes show vinculadas a proyecto/cotización
  - Controllers delgados: Form Requests → UseCases → redirect/view + `session('status')` como Proyectos.
  - Ownership: `abort_unless` cotización pertenece al proyecto.
  - LOGGING: INFO en acciones de estado/PDF/conversión a nivel controller o use case (no duplicar en exceso); WARN 403/422.
  - Files: `routes/web.php`, `app/Http/Controllers/QuotationController.php`, `InstallationOrderController.php`, `TraceabilityController.php`, `app/Http/Requests/**`

- [ ] Task 11: Vistas Blade/Alpine y Configuración IVA
  - Vistas: index cotizaciones, create/edit con líneas dinámicas (Alpine), show con totales y acciones por estado.
  - Enlace desde `projects/show` a cotizaciones del proyecto.
  - Extender `settings/edit` **o** sección dedicada para editar IVA vigente (sin hardcode).
  - Activar items sidebar `cotizaciones` y `trazabilidad` con `Route::has` real; no romper Home/Proyectos/Personal.
  - LOGGING: N/A en Blade; flash messages de UI.
  - Files: `resources/views/quotations/**`, `resources/views/traceability/**`, `resources/views/projects/show.blade.php`, `resources/views/settings/edit.blade.php`, `resources/views/components/sidebar.blade.php`

- [ ] Task 12: Descarga PDF y permisos de sesión
  - Acción `downloadPdf` / `stream` autenticada; nombre archivo `cotizacion-{code}.pdf`.
  - Regenerar PDF desde datos persistidos (snapshot IVA/totales/líneas), no desde tasa “actual” si ya congelada.
  - LOGGING: INFO descarga; WARN si cotización no emitible.
  - Files: controller + ruta + vista PDF (ajuste fino)

<!-- Commit checkpoint: tasks 10-12 -->

### Fase 5: Orden, Trazabilidad e Historial de punta a punta

- [ ] Task 13: Conversión Cotización aprobada → Orden de Instalación/Implementación
  - UI botón “Convertir a Orden” solo si `aprobada` y aún no convertida.
  - Transacción DB: crear `installation_orders_tb`, actualizar status cotización, audit + traceability.
  - Vista show de Orden (mínima) enlazada a Proyecto y Cotización origen.
  - LOGGING: INFO éxito con ids; WARN si ya existe orden; ERROR en rollback.
  - Files: use case (ya), controller, `resources/views/orders/**`

- [ ] Task 14: Módulo Trazabilidad (listado + detalle por proyecto)
  - Index filtrable por proyecto; timeline de eventos.
  - Integración: cada hito comercial escribe evento (no reutilizar `DvrSupport` como trazabilidad).
  - LOGGING: INFO consultas relevantes solo si útil; ERROR fallos de carga.
  - Files: `TraceabilityController`, views, recorder

- [ ] Task 15: Historial/auditoría de cambios importantes
  - Registrar: create/update líneas, cambio IVA snapshot/totales, cambios de estado, conversión, (opcional) descarga PDF.
  - UI: sección “Historial” en show de cotización (y/o enlace desde trazabilidad).
  - LOGGING: no loguear payloads sensibles completos a LOG si ya están en `audit_logs_tb`; INFO acción + id.
  - Files: `AuditLog` infra, partial Blade historial, hooks en use cases

<!-- Commit checkpoint: tasks 13-15 -->

## Criterios de aceptación (para `/aif-verify` futuro)

1. Cotización pertenece a un Proyecto existente.
2. Líneas dinámicas con producto, cantidad, marca, serie, precio unitario.
3. Totales recalculados en servidor; IVA desde setting configurable; `%` aplicado guardado en la cotización.
4. PDF profesional descargable vía Spatie Laravel PDF.
5. Solo cotización aprobada convierte a Orden; no se rompen módulos previos.
6. Eventos de trazabilidad + auditoría visibles.
7. MySQL como BD de las migraciones/features.

## Fuera de alcance de este plan

- Facturación completa (sidebar placeholder puede quedar `#` o mensaje “próximamente”)
- Rewrite de Camera/Proyectos a DDD
- Tests automatizados (Settings: Testing = no)
- Documentación formal obligatoria (Docs = no)

## Próximo paso

Revisar este plan. Cuando apruebes la implementación:

```
/aif-implement
```

Plan artifact: `.ai-factory/plans/cotizaciones-proyecto-orden-trazabilidad.md`
