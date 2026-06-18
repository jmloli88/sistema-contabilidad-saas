# Proposal: Dynamic "Honorarios Laudos" Fields

## Intent

Replace 3 hardcoded laudo honorarios fields (EGG, Potencial, Electromiografía) with dynamically generated fields — one per active exam. When admins add custom exams via `dynamic-exam-management`, laudo fields appear automatically without Blade edits.

## Scope

### In Scope
- Replace 3 hardcoded laudo `<div>` blocks in `create.blade.php` and `edit.blade.php` with an Alpine `x-for` loop over `examenesDisponibles`
- Dynamically initialize `gastos` keys (`honorarios_laudo_examen_{id}`) in `init()` for both blades
- Add `str_starts_with('honorarios_laudo_')` prefix handling in `RepaseService::normalizeGastos()` to categorize dynamic keys as `tipo => 'laudos'`
- Preserve 3 legacy keys in `$tipoMap` for backward compatibility

### Out of Scope
- Changing existing gasto fields (honorarios_medicos, tecnicos, gasolina, etc.)
- Database schema changes — `gasto_key` column already supports arbitrary keys
- Exam deactivation UI behavior (deactivated exams simply omit from the form; saved data persists)

## Capabilities

### New Capabilities
- `dynamic-laudos-honorarios`: Dynamic laudo honorarios fields rendered per active exam, with persistent `gasto_key` convention `honorarios_laudo_examen_{id}`

### Modified Capabilities
- None (no existing specs)

## Approach

**Naming convention**: `honorarios_laudo_examen_{id}` — ID-based, immune to exam renames.

**Blade (create + edit)**: Replace 3 hardcoded `<div>` blocks with `<template x-for="examen in examenesDisponibles">`. Use `@input` handlers (not `x-model`) to set `gastos[key]` since Alpine `x-model` does not support dynamic property paths. Key format: `'honorarios_laudo_examen_' + examen.id`.

**Alpine `init()`**: Iterate `examenesDisponibles` and initialize missing `gastos` keys to `0`.

**RepaseService**: Insert a `str_starts_with($tipoKey, 'honorarios_laudo_')` check before the generic `else` fallback (line ~421). Look up the exam by extracted ID for the `descripcion`; assign `tipo => 'laudos'`.

**No changes needed**: `calcularTotalGastos()` (already generic `Object.values().reduce()`), request validation (`gastos.*`), edit restoration loop (already dynamic), show blade (`$g->tipo === 'laudos'` filter).

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `resources/views/repases/create.blade.php` | Modified | Replace 3 hardcoded laudo divs (lines 274–290) with `x-for`; dynamic init (lines 546–556) |
| `resources/views/repases/edit.blade.php` | Modified | Same structural changes as create blade (lines 198–215, 471–500) |
| `app/Services/RepaseService.php` | Modified | Add prefix check in `normalizeGastos()` before generic `else` (line ~421) |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Dynamic laudo keys saved as `tipo => 'extra'` if prefix check fails | Low | Prefix check inserted before fallback; PHPUnit test covers dynamic key normalization |
| Alpine `x-model` incompatibility with dynamic paths | High | Use `@input` event handler instead: `gastos[key] = $event.target.value` |
| Deactivated exam has existing gasto data but no visible field | Low | Data preserved in DB and totals; acceptable UX — field simply not rendered |
| Exam deleted → orphaned `gasto_key` references | Low | Exam deletion blocked by FK or soft-delete; orphan keys still display via stored `descripcion` |

## Rollback Plan

1. Revert 3 files: `create.blade.php`, `edit.blade.php`, `RepaseService.php` to previous commits
2. No DB migration to undo — `gasto_key` column unchanged
3. Existing repases created with new keys remain valid (keys are just strings); old keys in `$tipoMap` untouched

## Dependencies

- `dynamic-exam-management` change must be complete (provides `examenesDisponibles` with active exams)
- `Examen::active()` scope must be functional in controller

## Success Criteria

- [ ] Create form renders one laudo input per active exam (no hardcoded fields)
- [ ] Edit form restores saved laudo values dynamically and renders current active exams
- [ ] Saving a repase with dynamic laudo keys produces `tipo => 'laudos'` rows with correct `descripcion`
- [ ] Existing repases with old keys (`honorarios_laudos_egg`, etc.) load and display correctly in edit/show
- [ ] `calcularTotalGastos()` includes all dynamic laudo values in the total
- [ ] PHPUnit tests pass for dynamic key normalization in `RepaseService`
