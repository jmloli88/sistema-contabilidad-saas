# Tasks: Dynamic "Honorarios Laudos" Fields

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 150–200 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | single-pr |
| Chain strategy | size-exception |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: size-exception
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Backend + Frontend + Tests | PR 1 | Single PR to main; size-exception not needed (< 400 lines) |

## Phase 1: Backend Tests — RED

- [x] 1.1 Write `RepaseServiceTest::test_dynamic_laudo_key_normalizes_to_laudos_tipo()` — create Examen, pass `honorarios_laudo_examen_{id}`, assert `tipo='laudos'` and correct description
- [x] 1.2 Write `RepaseServiceTest::test_legacy_keys_still_resolve_via_tipo_map()` — pass `honorarios_laudos_egg`, assert `tipo='laudos'` unchanged
- [x] 1.3 Write `RepaseServiceTest::test_unknown_key_falls_back_to_extra_tipo()` — pass `custom_fee`, assert `tipo='extra'`

## Phase 2: Backend Implementation — GREEN

- [x] 2.1 In `app/Services/RepaseService.php` `normalizeGastos()`: insert `str_starts_with($tipoKey, 'honorarios_laudo_examen_')` guard before the `else` fallback — extract `examen_id`, `Examen::find()`, build normalized row with `tipo => 'laudos'`
- [x] 2.2 Verify PHPUnit tests pass: `php artisan test --filter RepaseServiceTest --stop-on-failure` ✅ 12/12 pass

## Phase 3: Frontend — create.blade.php

- [x] 3.1 Remove 3 hardcoded laudo `<div>` blocks (lines ~274–290) from GASTOS OPERATIVOS grid
- [x] 3.2 Replace with `<template x-for="examen in examenesDisponibles">` rendering dynamic input with `@input` handler: `gastos['honorarios_laudo_examen_' + examen.id] = parseFloat($event.target.value) || 0; calcularTotalGastos()`
- [x] 3.3 In Alpine `init()`, remove 3 hardcoded laudo keys from `gastos`; add `examenesDisponibles.forEach()` loop to initialize keys to `0`; add `old('gastos')` restoration for laudo keys

## Phase 4: Frontend — edit.blade.php

- [x] 4.1 Remove 3 hardcoded laudo `<div>` blocks (lines ~198–215) from GASTOS OPERATIVOS grid
- [x] 4.2 Replace with same `<template x-for>` pattern and `@input` handler as create blade
- [x] 4.3 In Alpine `init()`, remove 3 hardcoded laudo keys from `gastos` (lines ~476–478); add conditional dynamic init loop after the gasto restoration loop (preserves saved values, defaults missing keys to 0)

## Phase 5: Integration Test — RED/GREEN

- [ ] 5.1 Write failing feature test: POST create repase with `gastos[honorarios_laudo_examen_{id}]`, assert laudo row saved with correct `tipo`, `gasto_key`, and `descripcion`
- [~] 5.2 Run full test suite: existing `RepaseServiceTest` covers normalize behavior; 3 pre-existing failures in Predictive/PreventDuplicateSubmissions are unrelated

## Phase 6: Verification

- [ ] 6.1 Manually verify: 3 legacy repases still render correct laudo fields on edit/show
- [ ] 6.2 Manually verify: create form shows one laudo input per active exam with correct labels
- [ ] 6.3 Manually verify: edit form pre-populates saved values and defaults new exams to 0
- [ ] 6.4 Run `vendor/bin/pint --test` to confirm style compliance
