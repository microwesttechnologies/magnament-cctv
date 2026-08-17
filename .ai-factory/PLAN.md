# Plan — Performance extrema + caché inteligente

## Objetivo

Acelerar consultas y navegación de Management CCTV sin alterar lógica de negocio ni reescribir Domain/Application. Auditar → medir → optimizar queries → cachear solo lo seguro → invalidar con precisión.

## Hallazgos de auditoría (antes)

| Área | Problema |
|------|----------|
| Dashboard | 4 `count()` + atención + actividad (~6 queries). User no usado en actividad. |
| Project show | No carga `installationOrders`; Blade usa `$order->quotation` → N+1 |
| Listados | Proyectos, cotizaciones, personal: `get()` sin paginar |
| Trazabilidad | `limit(200)` + eager de quotation/order/user no usados en la vista |
| Quotation show | Doble carga de `lines` (repo + Eloquent) |
| VAT | Query a `app_settings_tb` en cada cotización |
| Índices | Faltan `status`/`created_at` sueltos usados en dashboard |
| Caché | `CACHE_STORE=database`. Redis en config pero **phpredis no está instalado** |
| Motion | Intercept de navegación espera 180ms (parece lento) |
| HTTP | HTML autenticado no debe ir a caché pública |

**No cachear:** listados filtrados/paginados, permisos, HTML auth, estados transaccionales.

## Estrategia de caché

Driver: el configurado (`database` por defecto). Arquitectura lista para `CACHE_STORE=redis` cuando exista extensión.

| Clave | TTL | Invalidación |
|-------|-----|----------------|
| `dashboard.snapshot.global` | 60s | Project, Quotation, InstallationOrder, TraceabilityEvent |
| `projects.stats.global` | 60s | Project, Dvr |
| `catalog.projects.picker` | 30 min | Project saved/deleted |
| `catalog.staff.technicians.active` | 30 min | Staff saved/deleted |
| `settings.vat_rate_percent` | 45 min | VAT update |

Sin `Cache::flush()`. Sin tags (database driver no las soporta). Datos globales: no hay tenant; KPIs no dependen del usuario (el nombre va en la vista, no en caché).

## Tareas

- [x] CacheKeys, CacheTtl, CacheInvalidator, observers
- [x] Dashboard snapshot + stats agrupados
- [x] Paginación + select + eager load en listados
- [x] N+1 project show; quitar eager innecesario
- [x] VAT cache + invalidación
- [x] Migration nueva de índices
- [x] HTTP `private, no-store`; motion 90ms; fonts preconnect
- [x] Query log opt-in (no producción)
- [x] Tests de presupuesto de queries + invalidación
- [x] `npm run build` + PHPUnit + aif-verify
- [x] Commit `perf(app): optimize queries and implement intelligent caching`

## Medición (PHPUnit, sqlite :memory:, CACHE_STORE=array)

| Página | Antes (auditoría) | Después |
|--------|-------------------|---------|
| Dashboard (miss) | ~6 queries de datos | 7 queries totales (auth + 5 datos agrupados) |
| Dashboard (hit) | n/a | 0 queries SQL (sesión array + snapshot) |
| Project show 1 orden | 1 + N quotations lazy | 7 queries |
| Project show 8 órdenes | 1 + 8 quotations | 7 queries (no crece) |
