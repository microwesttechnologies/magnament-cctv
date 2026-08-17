# Magnament CCTV — Descripción del proyecto

## Resumen

Aplicación web **existente y operativa** para gestión técnica de proyectos CCTV (CCTV Manager). El setup de AI Factory prepara el contexto de agentes para **extender** el sistema — no para crear un proyecto nuevo ni reemplazar módulos que ya funcionan.

## Funcionalidades actuales (conservar)

- Autenticación web por sesión (login/logout)
- Dashboard y shell (layout, sidebar, configuración de usuario)
- **Proyectos**: alta, detalle, estados, eliminación
- **Planos de piso** asociados a proyecto (upload/delete)
- **DVRs** por proyecto (CRUD) y **soportes técnicos** con evidencias
- **Cámaras de proyecto** (CRUD sobre plano/proyecto)
- **Personal** (CRUD de staff y herramientas)
- API REST parcial de cámaras (`routes/api.php`) con capa DDD en el bounded context `Camera`

En la navegación ya existen entradas placeholder para **Cotizaciones**, **Trazabilidad** y **Facturación** (rutas aún por completar donde falten).

## Próximo alcance de producto (documentado, no implementado en este setup)

Flujo objetivo: **Proyecto → Cotización → Orden de Instalación/Implementación → Trazabilidad**

1. Módulo de Cotizaciones
2. Cotización asociada a un Proyecto
3. Descripción del trabajo
4. Productos/servicios dinámicos en líneas
5. Por línea: producto, cantidad, marca, serie, precio unitario
6. Cálculo automático de subtotal, IVA y total
7. IVA dinámico y configurable
8. Generación de PDF con formato genérico profesional
9. Descarga del PDF
10. Estados de cotización
11. Convertir cotización aprobada en Orden de Instalación/Implementación
12. Integración con el módulo de Trazabilidad existente
13. Relación Proyecto → Cotización → Orden
14. Historial/auditoría de cambios importantes

## Stack tecnológico

- **Lenguaje:** PHP 8.2
- **Framework:** Laravel 12
- **Frontend:** Blade, Alpine.js, Vite, Tailwind CSS 4
- **Base de datos:** **MySQL** (base de datos real del producto; no usar SQLite ni PostgreSQL en decisiones de arquitectura/implementación)
- **ORM:** Eloquent
- **Tests:** PHPUnit
- **Calidad:** Laravel Pint

> Nota: plantillas/defaults de Laravel pueden mencionar SQLite (p. ej. `.env.example`). Eso **no** define el entorno de producto: la BD real es MySQL.

## Patrones identificados

- Controladores HTTP + modelos Eloquent + vistas Blade para la mayoría de módulos (Proyectos, Personal, DVRs, Settings)
- Bounded context `Camera` con capas `Domain` / `Application` / `Infrastructure` (entidades, value objects, use cases, repositorio Eloquent)
- Form Requests donde aplica (p. ej. `StoreCameraRequest`)
- Migraciones con tablas de negocio sufijo `_tb` (`projects_tb`, `dvrs_tb`, etc.)
- UI en español; rutas de negocio con nombres mixtos (`projects`, `personal`, `configuracion`)

## Notas de arquitectura

- Monolito Laravel web-first; ampliar módulos sin greenfield
- Preferir reutilizar layout, sidebar, auth middleware y convenciones Eloquent existentes
- Extender DDD solo donde aporte (dominio rico como Cotizaciones); no forzar rewrite de módulos Eloquent ya estables
- Dependencias nuevas (PDF, auditoría) deben integrarse sin romper flujos actuales

## Requisitos no funcionales

- **Logging:** canales Laravel (`LOG_CHANNEL` / `LOG_LEVEL`)
- **Errores:** validación Laravel + excepciones de dominio donde exista capa Domain
- **Seguridad:** auth de sesión, CSRF en formularios web, no exponer secretos; escaneo de skills externas antes de usarlas
- **Datos:** persistencia MySQL; migraciones compatibles con MySQL
- **Compatibilidad:** conservar todas las funcionalidades existentes al añadir Cotizaciones/Órdenes

## Arquitectura

Ver [.ai-factory/ARCHITECTURE.md](ARCHITECTURE.md) para el patrón adoptado (módulos estructurados adaptados al monolito Laravel existente), reglas de dependencia y cómo extender Cotizaciones sin rewrite.
