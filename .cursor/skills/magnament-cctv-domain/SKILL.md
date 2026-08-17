---
name: magnament-cctv-domain
description: Dominio CCTV Manager de Magnament: proyectos, planos, DVRs, cámaras, personal y reglas para extender sin romper módulos existentes. Usar al diseñar o implementar features sobre el sistema Laravel actual.
metadata:
  author: magnament-aif
  version: "1.0"
---

# Magnament CCTV — Dominio del producto

## Overview

Contexto de negocio y límites del monolito Laravel **CCTV Manager**. El sistema ya opera; los agentes deben extender, no reemplazar.

## Guías

### Conservar lo existente
- Auth por sesión, dashboard, layout/sidebar, configuración de usuario
- Proyectos, planos (`FloorPlan`), DVRs, soportes DVR, cámaras de proyecto, personal
- No reescribir módulos estables solo para “alinear arquitectura”

### Base de datos
- Producto real: **MySQL**
- No diseñar sobre SQLite ni PostgreSQL
- Respetar convención de tablas de negocio `*_tb` cuando se añadan entidades nuevas

### UI y rutas
- Blade + Alpine + Tailwind; reutilizar `components.layout` / `components.sidebar`
- Entradas de nav ya previstas: Cotizaciones, Trazabilidad, Facturación — completar rutas reales sin inventar otro shell

### Relación de producto objetivo
`Proyecto → Cotización → Orden de Instalación/Implementación → Trazabilidad`

### Cuándo usar DDD vs Eloquent directo
- Eloquent + controlador: CRUD y orquestación simple (como Proyectos/Personal)
- Domain/Application/Infrastructure: reglas de negocio ricas (estados, cálculos, conversiones) — ver skill `laravel-ddd-magnament` y patrón Camera

## Checklist

- [ ] ¿La feature toca MySQL con migraciones Eloquent compatibles?
- [ ] ¿Se reutiliza auth/middleware/layout existentes?
- [ ] ¿Se evita romper Proyectos/DVRs/Cámaras/Personal?
- [ ] ¿Queda claro el vínculo con Cotización/Orden/Trazabilidad si aplica?
