# Reglas base del proyecto

> Convenciones detectadas automáticamente desde el código. Editar según necesidad.

## Convenciones de nombres

- Archivos PHP: PascalCase (`ProjectController.php`, `EloquentCameraRepository.php`)
- Variables/métodos: camelCase (`storeFloorPlan`, `ipAddress`)
- Clases: PascalCase; controladores y entidades de dominio suelen ser `final class`
- Namespaces PSR-4 bajo `App\`
- Tablas de negocio: snake_case con sufijo `_tb` (`projects_tb`, `dvrs_tb`, `cameras_tb`)
- Rutas: nombres en kebab/dot Laravel (`projects.show`, `staff.index`); UI en español

## Estructura de módulos

- `app/Http/Controllers` + `app/Models` + `resources/views` — módulos operativos actuales
- `app/Domain`, `app/Application`, `app/Infrastructure` — bounded context Camera (DDD parcial)
- `app/Http/Requests` — validación de entrada HTTP
- `routes/web.php` — app autenticada; `routes/api.php` — API de cámaras
- No crear un segundo proyecto ni duplicar el shell (layout/sidebar)

## Manejo de errores

- Validación con `$request->validate([...])` o Form Requests
- Excepciones de dominio específicas donde exista Domain (p. ej. `CameraNotFoundException`)
- Preferir early return / guard clauses en controladores y use cases

## Control de flujo

- Preferir flujo plano y legible frente a condicionales anidados profundos
- Usar guard clauses, `return`/`continue` tempranos y helpers pequeños con nombre claro
- Tratar casos borde primero para dejar visible el camino principal

## Logging

- Stack de logging de Laravel (`LOG_CHANNEL`, `LOG_LEVEL`); no introducir loggers ad hoc sin necesidad

## Base de datos

- Producto real: **MySQL**
- No diseñar ni documentar features nuevas sobre SQLite o PostgreSQL
- Migraciones Eloquent compatibles con MySQL; mantener estilo de tablas existentes cuando se añadan módulos

## Extensión del sistema (Cotizaciones y siguientes)

- Conservar auth, proyectos, planos, DVRs, cámaras, personal y settings
- Reutilizar patrones UI (Blade + Alpine) y middleware `auth`
- Relación objetivo: Proyecto → Cotización → Orden → Trazabilidad
- Auditoría de cambios importantes sin romper flujos actuales
- No reemplazar funcionalidades existentes al implementar módulos nuevos
