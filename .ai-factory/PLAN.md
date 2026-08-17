# Implementation Plan: Global Motion Design System

Branch: feature/autonomous-uiux-transformation
Created: 2026-08-17

## Original Request

FASE 8 — MOTION DESIGN + MICROINTERACTIONS. Capa de motion sobre Magnament Ops UI v1.1. Page transitions, microinteracciones, tokens, reduced motion. No SPA. No dependencias nuevas.

## Settings
- Testing: yes
- Logging: standard
- Docs: no

## Roadmap Linkage
Milestone: "none"
Rationale: Capa UX de motion; no hito de dominio.

## Estrategia (UI/UX Pro Max)

- Motion explica, no decora. Máximo 1–2 elementos clave por vista (contenido main, no shell).
- ease-out en entrada, ease-in en salida. Tokens compartidos; no duraciones sueltas.
- Preferir transform + opacity. Sidebar/header estables.
- Page transitions: interceptar GET same-origin con exclusiones (download, target, modifier keys, hash, externo, /pdf). Fallback fade+translate 12px. Dirección heurística por profundidad de path.
- prefers-reduced-motion: sin slide/blur/scale/stagger.
- Sin librerías nuevas (CSS + Alpine + JS).

## Tasks

- [x] Task 1: Tokens + keyframes + utilities en `resources/css/app.css`.
- [x] Task 2: Page transition segura en `resources/js/app.js` + markup shell.
- [x] Task 3: Microinteracciones en componentes UI (button, card, table, modal, drawer, dropdown, toast, tabs, empty, skeleton, alert, sidebar).
- [x] Task 4: Stagger dashboard, tab panels de proyecto, skip PDF.
- [x] Task 5: Build, tests, verify.
