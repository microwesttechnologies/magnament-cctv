# Arquitectura: Módulos estructurados adaptados al monolito Laravel existente

## Overview

Magnament CCTV es un **monolito Laravel 12** ya en producción/uso con UI Blade/Alpine y persistencia **MySQL** vía Eloquent. La arquitectura documentada aquí **describe y guía la extensión del código real**, no un greenfield ni un rewrite.

El patrón base es **Structured Modules** (módulos por área de negocio) con **DDD parcial** ya presente en el contexto `Camera`. Los módulos nuevos con reglas ricas (Cotizaciones, Orden, auditoría) deben inclinarse a use cases / dominio aislado; los módulos CRUD estables permanecen en controladores + Eloquent.

## Decision Rationale

- **Tipo de proyecto:** aplicación web de gestión CCTV existente
- **Tech stack:** PHP 8.2, Laravel 12, Eloquent, MySQL, Blade, Alpine, Vite, Tailwind
- **Factor clave:** reutilizar estructura y comportamientos actuales; añadir Cotizaciones → Orden → Trazabilidad sin romper Proyectos/DVRs/Personal
- **Alineación Step 1.5:** adaptar guías a la estructura existente (opción “documentar realidad”), no imponer árbol ideal que obligue a refactor masivo

## Folder Structure

```
app/
├── Http/
│   ├── Controllers/          # Presentation web (Auth, Project, Dvr, Staff, Settings…)
│   └── Requests/             # Form Requests
├── Models/                   # Eloquent de módulos operativos actuales
├── Domain/
│   └── Camera/               # Dominio rico existente (entidades, VOs, puertos)
├── Application/
│   └── Camera/               # Use cases / DTOs Camera
├── Infrastructure/
│   └── Persistence/Eloquent/ # Adaptadores Camera → MySQL
└── Providers/

resources/views/              # Blade (layout, sidebar, módulos UI)
routes/web.php                # Rutas autenticadas
routes/api.php                # API cámaras

# Extensión prevista (cuando se implemente — no creada por /aif):
# Cotizaciones / Órdenes: preferir Domain+Application+Infrastructure
# o servicios de aplicación dedicados; UI en views + Controllers;
# FK a projects_tb; integración con Trazabilidad y auditoría.
```

## Dependency Rules

- ✅ `Http` → `Application` / `Models` (según módulo)
- ✅ `Application` → `Domain` + puertos (interfaces)
- ✅ `Infrastructure` → implementa puertos con Eloquent/MySQL
- ✅ Nuevos módulos pueden leer `Project` (Eloquent existente) por ID/relación
- ❌ `Domain` → Eloquent, HTTP, Blade, Storage
- ❌ Diseñar features nuevas asumiendo SQLite o PostgreSQL
- ❌ Reescribir módulos estables solo para “cumplir capas”

## Layer/Module Communication

- **Módulos legacy (Proyectos, Personal, DVR):** Controller valida → Eloquent → View/Redirect
- **Contexto Camera:** Controller/API → UseCase → RepositoryInterface → EloquentCameraRepository
- **Cotizaciones (futuro):** orquestar cálculos/estados/conversión en Application/Domain; persistir MySQL; emitir/registrar eventos hacia Trazabilidad y auditoría
- **Cadena de negocio:** `Proyecto` → `Cotización` → `Orden` → `Trazabilidad`

## Key Principles

1. Extender, no reemplazar: auth, proyectos, planos, DVRs, cámaras, personal y settings permanecen
2. MySQL es la fuente de verdad de persistencia del producto
3. Reglas de dinero/IVA/estados viven en servidor, no solo en el cliente
4. PDF y descarga son adapters de presentación/infra, no contaminan el dominio
5. Auditoría de cambios importantes sin sustituir el módulo de Trazabilidad: integrarlo
6. UI: reutilizar layout/sidebar; completar rutas `cotizaciones` / `trazabilidad` cuando existan

## Code Organization Note

- **Features nuevas:** seguir estas guías donde sea práctico (especialmente Cotizaciones)
- **Código existente:** documentado as-is; no forzar rewrite al tocar bugs o features adyacentes
- **Interoperabilidad:** al consumir `Project` u otros modelos legacy desde un módulo nuevo, preferir FKs/servicios claros sin refactor cosmético del legacy

## Code Examples

### Use case fino (estilo Camera) para una regla de cotización

```php
// Ilustrativo — no implementado por /aif
final class RecalculateQuoteTotalsUseCase
{
    public function execute(Quote $quote, VatRate $vatRate): Quote
    {
        $subtotal = $quote->linesSubtotal();
        $vat = $subtotal->multiply($vatRate);
        return $quote->withTotals($subtotal, $vat, $subtotal->add($vat));
    }
}
```

### Relación Eloquent con proyecto existente

```php
// Ilustrativo — alineado al estilo Models/ actual
final class Quote extends Model
{
    protected $table = 'quotes_tb'; // nombre final a definir en el plan

    public function project()
    {
        return $this->belongsTo(Project::class);
    }
}
```

## Anti-Patterns

- ❌ Crear un segundo proyecto Laravel o un frontend paralelo “solo para cotizaciones”
- ❌ Hardcodear IVA en Blade sin configuración
- ❌ Convertir a Orden sin estado aprobado
- ❌ Usar SQLite/Postgres en migraciones/docs de features nuevas
- ❌ Meter lógica de totales solo en Alpine sin recálculo server-side
- ❌ Sustituir Trazabilidad por un log genérico desconectado del producto
