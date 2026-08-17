---
name: magnament-quotations
description: Módulo de Cotizaciones Magnament CCTV: líneas dinámicas, IVA configurable, totales, estados, PDF, conversión a Orden e integración con Trazabilidad/auditoría. Usar al planificar o implementar cotizaciones.
metadata:
  author: magnament-aif
  version: "1.0"
---

# Magnament — Cotizaciones

## Overview

Guía de producto/técnico para el módulo de Cotizaciones sobre el Laravel existente. **No implementa por sí sola**; orienta planes e implementaciones futuras.

## Requisitos funcionales

1. Módulo de Cotizaciones en la app actual
2. Cotización asociada a un **Proyecto** existente
3. Descripción del trabajo
4. Líneas dinámicas de productos/servicios
5. Por línea: producto, cantidad, marca, serie, precio unitario
6. Cálculo automático: subtotal, IVA, total
7. IVA dinámico y configurable (no hardcodear en vistas)
8. PDF formato genérico profesional
9. Descarga del PDF
10. Estados de cotización (definir máquina de estados en el plan)
11. Solo cotización **aprobada** → Orden de Instalación/Implementación
12. Integración con Trazabilidad existente
13. Cadena: Proyecto → Cotización → Orden
14. Historial/auditoría de cambios importantes

## Reglas de diseño

### Persistencia
- MySQL + Eloquent; migraciones nuevas sin alterar datos de módulos vivos salvo FKs necesarias
- Cotización `belongsTo` Project; Orden referencia Cotización (y Proyecto)

### Cálculos
- Subtotal = suma(cantidad × precio unitario)
- IVA = subtotal × tasa configurable
- Total = subtotal + IVA
- Recalcular en servidor al guardar; no confiar solo en el cliente

### Estados
- Transiciones explícitas; bloquear conversión a Orden si no está aprobada
- Auditar cambios de estado

### PDF
- Preferir generación desde Blade (p. ej. skill `laravel-pdf` / spatie) sin acoplar UI operativa al PDF
- Descarga vía ruta autenticada

### Auditoría
- Registrar cambios importantes: creación, edición de líneas, cambios de IVA/totales, cambios de estado, conversión a Orden, regeneración/descarga PDF si es relevante de negocio
- No sustituir el módulo de Trazabilidad: integrar eventos/enlaces hacia él

### No hacer
- No crear app nueva ni duplicar Proyectos
- No usar SQLite/Postgres como BD de diseño
- No convertir cotizaciones no aprobadas
- No romper sidebar/rutas existentes al añadir rutas `cotizaciones`

## Checklist de implementación (cuando se pida)

- [ ] Modelo/migración Cotización + líneas en MySQL
- [ ] CRUD/UI asociado a Proyecto
- [ ] IVA configurable + recálculo servidor
- [ ] Estados + guard de conversión
- [ ] PDF + descarga auth
- [ ] Orden desde cotización aprobada
- [ ] Hook/integración Trazabilidad
- [ ] Auditoría de cambios clave
- [ ] Tests de cálculo, estados y conversión
