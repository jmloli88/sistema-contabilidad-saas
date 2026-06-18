# Dynamic Laudos Honorarios Specification

## Purpose

Render honorarios laudos inputs dynamically — one per active exam — replacing the 3 hardcoded fields (EGG, Potencial, Electromiografía). New exams auto-appear without Blade edits.

## Requirements

### Requirement: Dynamic Field Rendering

The system MUST render one numeric laudo input per active exam in the GASTOS OPERATIVOS section, driven by `examenesDisponibles`.

| Property | Value |
|----------|-------|
| **Source** | `examenesDisponibles` (active exams only) |
| **Label** | `Honorarios Laudos {exam.nombre}` |
| **Field name** | `gastos[honorarios_laudo_examen_{id}]` |
| **Input mechanism** | `@input` event handler (not `x-model` — dynamic paths unsupported by Alpine.js) |
| **Init default** | `0` for each active exam without a saved value |

#### Scenario: Form renders one field per active exam

- GIVEN 5 active exams exist
- WHEN the create repase form loads
- THEN 5 laudo inputs are rendered with labels matching exam names

#### Scenario: New exam appears without Blade changes

- GIVEN admin adds a new active exam "Biopsia" via dynamic-exam-management
- WHEN the create form loads next time
- THEN a "Honorarios Laudos Biopsia" input appears automatically

#### Scenario: Edit form restores saved values

- GIVEN a repase with `gastos[honorarios_laudo_examen_1] = 150.00` and `gastos[honorarios_laudo_examen_3] = 75.00`
- WHEN the edit form loads
- THEN fields for exam 1 and exam 3 are pre-populated with their saved values
- AND fields for exams without saved values default to 0

### Requirement: Naming Convention

Dynamic laudo keys MUST follow the format `honorarios_laudo_examen_{id}`, where `{id}` is the exam's primary key. This convention is ID-based and immune to exam renames.

Legacy keys (`honorarios_laudos_egg`, `honorarios_laudos_potencial`, `honorarios_laudo_electromiografia`) SHALL remain in `$tipoMap`.

#### Scenario: Key survives exam rename

- GIVEN exam ID 7 named "Minimental" is renamed to "MMSE"
- THEN existing repases with key `honorarios_laudo_examen_7` remain valid
- AND new repases use the same key `honorarios_laudo_examen_7`

### Requirement: Save and Normalize

`RepaseService::normalizeGastos()` MUST detect the `honorarios_laudo_` prefix via `str_starts_with()` and assign `tipo => 'laudos'` before the generic `else` fallback.

| Input key | Detection | Output tipo | Output descripcion |
|-----------|-----------|-------------|-------------------|
| `honorarios_laudo_examen_5` | Prefix match | `laudos` | `Honorarios Laudos {exam.nombre}` |
| `honorarios_laudos_egg` | Legacy `$tipoMap` | `laudos` | `Honorarios Laudos EGG` |
| `custom_fee` | Generic fallback | `extra` | `Custom fee` |

#### Scenario: Dynamic key saves as laudos type

- GIVEN form submits `gastos[honorarios_laudo_examen_5] = 200.00`
- WHEN `normalizeGastos()` processes the input
- THEN a row is created with `tipo => 'laudos'`, `gasto_key => 'honorarios_laudo_examen_5'`, `monto => 200.00`
- AND `descripcion` includes the exam's name at save time

#### Scenario: Legacy key saves as laudos type

- GIVEN form submits `gastos[honorarios_laudos_egg] = 100.00`
- WHEN `normalizeGastos()` processes the input
- THEN a row is created with `tipo => 'laudos'`, `descripcion => 'Honorarios Laudos EGG'`

### Requirement: Deactivated Exam Handling

The system SHALL NOT render input fields for deactivated exams (`is_active = false`), but SHALL preserve their saved values in `gastos` for total calculation and display.

#### Scenario: Deactivated exam hidden, value preserved in total

- GIVEN exam ID 3 is deactivated, repase has `gastos[honorarios_laudo_examen_3] = 75.00`
- WHEN the edit form loads
- THEN no input field is rendered for exam 3
- AND `gastos.honorarios_laudo_examen_3` equals 75.00
- AND the value is included in `totalGastos`

#### Scenario: Show blade displays deactivated exam laudo

- GIVEN a saved repase with a `tipo => 'laudos'` row for a deactivated exam
- WHEN the show blade renders GASTOS OPERATIVOS (filtering by `tipo === 'laudos'`)
- THEN the row is displayed with its stored descripcion and monto

### Requirement: Total Calculation

The system MUST include all dynamic and legacy laudo values in `totalGastos` via the existing `calcularTotalGastos()` method, which reduces `Object.values(this.gastos)`. No code change is required.

#### Scenario: Dynamic laudos included in total

- GIVEN `gastos` contains `honorarios_laudo_examen_1: 100`, `honorarios_laudo_examen_2: 200`, and `honorarios_medicos: 500`
- WHEN `calcularTotalGastos()` executes
- THEN `totalGastos` equals 800
