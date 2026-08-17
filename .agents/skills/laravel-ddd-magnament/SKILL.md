---
name: laravel-ddd-magnament
description: Cuándo y cómo aplicar DDD parcial en Magnament CCTV (patrón Camera) frente a Eloquent directo en controladores. Usar al diseñar módulos nuevos con reglas de negocio.
metadata:
  author: magnament-aif
  version: "1.0"
---

# Laravel DDD parcial — Magnament

## Overview

El proyecto mezcla **Eloquent + controladores** (mayoría) con un bounded context **Camera** en `Domain` / `Application` / `Infrastructure`. Esta skill define cuándo extender cada estilo.

## Cuándo Eloquent directo

Usar el estilo de `ProjectController` / `StaffController` cuando:
- CRUD y validación son el foco
- Pocas reglas de estado
- Ya existe el patrón en módulos vecinos

Estructura típica: `Http/Controllers` + `Models` + `views` + Form Request opcional.

## Cuándo DDD parcial (como Camera)

Usar capas Domain/Application/Infrastructure cuando:
- Hay máquina de estados, invariantes o cálculos de negocio (p. ej. Cotizaciones)
- Se necesita aislar reglas de HTTP/Eloquent
- Habrá múltiples puntos de entrada (web + API + jobs) sobre la misma lógica

Referencia existente:
- `app/Domain/Camera/...`
- `app/Application/Camera/UseCases/...`
- `app/Infrastructure/Persistence/Eloquent/...`
- Binding en `RepositoryServiceProvider`

## Reglas de dependencia

- Domain no importa Eloquent, HTTP ni Blade
- Application orquesta Domain + puertos (repositorios)
- Infrastructure implementa puertos con Eloquent/MySQL
- Presentation (`Http`) llama Application o, en módulos legacy, Models directamente

## Extensión recomendada para Cotizaciones

- Preferir DDD parcial o al menos servicios/use cases para: totales, IVA, transiciones de estado, conversión a Orden
- Reutilizar `Project` Eloquent existente vía ID/FK; no reimplementar Proyectos en Domain salvo necesidad clara

## Anti-patrones

- Reescribir Personal/Proyectos a DDD “por consistencia”
- Meter reglas de IVA solo en Alpine/JS
- Que entidades de Domain conozcan `Request` o `Storage`
