# Design: Dynamic "Honorarios Laudos" Fields

## Technical Approach

Replace 3 hardcoded laudo `<div>` blocks in create/edit blades with an Alpine.js `x-for` loop over `examenesDisponibles`. Dynamic keys `honorarios_laudo_examen_{id}` are initialized in `init()`, rendered with `@input` handlers (not `x-model` — Alpine cannot bind dynamic property paths), and auto-summed by existing `calcularTotalGastos()` (`Object.values(this.gastos)`). Backend adds a `str_starts_with('honorarios_laudo_examen_')` prefix check in `normalizeGastos()` before the `else` fallback, resolving `tipo => 'laudos'` via `Examen::find($id)`. The 3 old keys remain in `$tipoMap` for backward compatibility.

## Architecture Decisions

### Decision 1: Dynamic key naming

| Option | Tradeoff | Decision |
|--------|----------|----------|
| `honorarios_laudo_examen_{id}` | Immutable, traceable, joins to Examen; verbose | ✅ Chosen |
| `laudo_{examen.nombre}` (slugified) | Name collisions, breaks on rename | Rejected |
| `honorarios_laudo_{examen.nombre}` | Same slug issues, inconsistent with old "laudos" plural | Rejected |

**Rationale**: ID-based keys survive exam renames, provide unambiguous traceability, and the `str_starts_with` prefix check is trivial to maintain. A prefix registry is simpler than managing a growing dynamic `$tipoMap`.

### Decision 2: Input binding strategy for dynamic keys

| Option | Tradeoff | Decision |
|--------|----------|----------|
| `@input` handler: `gastos['key'] = $event.target.value` | Explicit, works with Alpine's reactivity for dynamic keys | ✅ Chosen |
| `x-model.number` with dynamic expression | Alpine 3 `x-model` does NOT support dynamic expression evaluation | Rejected |
| Computed getter/setter wrapper per key | O(n) wrapper objects in init; overkill for `<input>` | Rejected |

**Rationale**: Alpine.js evaluates `x-model` expressions at compile time and cannot resolve dynamic property paths like `'gastos.' + key`. The `@input` handler is the standard Alpine pattern for dynamic keys and already familiar in the codebase.

### Decision 3: Backend detection — prefix match vs dynamic map

| Option | Tradeoff | Decision |
|--------|----------|----------|
| `str_starts_with('honorarios_laudo_')` prefix + `Examen::find($id)` | 1 DB query per laudo; simple, no map explosion | ✅ Chosen |
| Add every exam to `$tipoMap` dynamically | Map grows unbounded; must rebuild on every exam change; stale on deactivation | Rejected |
| Generic `tipo => 'laudos'` for any key matching regex | Ambiguous — could match non-exam keys with `honorarios_laudo_` prefix | Rejected |

**Rationale**: The prefix check + `Examen::find()` is idempotent and self-documenting. The `else` fallback stays as `'extra'` for truly unknown keys. A dynamic map would require maintaining synchronization with the `examenes` table.

## Data Flow

```
User types laudo amount
  │
  ▼
@input handler updates gastos['honorarios_laudo_examen_{id}']
  │
  ├──► calcularTotalGastos() sums Object.values(this.gastos)
  │         │
  │         └──► calcularTotalNeto()
  │
  ▼ (form POST)
gastos[honorarios_laudo_examen_{id}] → $request->gastos
  │
  ▼
RepaseService::normalizeGastos($gastos)
  │  foreach key → str_starts_with('honorarios_laudo_') ?
  │  YES: Examen::find(extractedId) → tipo='laudos', desc='Honorarios Laudos {name}'
  │  EXISTING KEY: $tipoMap match → legacy behavior preserved
  │  NO: else → tipo='extra' (fallback)
  │
  ▼
DB: gastos table row with tipo='laudos', gasto_key='honorarios_laudo_examen_{id}'
  │
  ▼
show.blade.php: $gastosOperativos filter → $g->tipo === 'laudos' ✓
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `resources/views/repases/create.blade.php` | Modify | Lines 274–290: replace 3 laudo `<div>` blocks with `<template x-for>`; lines 551–553: remove hardcoded laudo keys from `gastos` object; lines 607–628: add `examenesDisponibles.forEach` in `init()` to init dynamic keys; add `@input` handler for laudo inputs |
| `resources/views/repases/edit.blade.php` | Modify | Lines 198–215: replace 3 laudo `<div>` blocks with `<template x-for>`; lines 476–478: remove hardcoded laudo keys from `gastos` object; lines 526–622: add dynamic key init in `init()` (restoration loop at lines 542–599 already dynamic — no change) |
| `app/Services/RepaseService.php` | Modify | Lines 396-432: insert `str_starts_with('honorarios_laudo_')` handler before the `else` fallback; keep existing 3 keys in `$tipoMap` (lines 371-373) |
| `tests/Feature/RepaseServiceTest.php` | Modify | Add tests: (1) dynamic laudo key → `tipo='laudos'`, (2) old keys still resolve correctly, (3) unknown key → `tipo='extra'` fallback |

## Interfaces / Contracts

**gasto_key naming contract**: All laudo honorarios keys follow the pattern:
```
honorarios_laudo_examen_{id}
```
Where `{id}` is `examenes.id` (integer). Legacy keys (`honorarios_laudos_egg`, `honorarios_laudos_potencial`, `honorarios_laudo_electromiografia`) are preserved but no longer generated for new repases.

**normalizeGastos contract**: Any key starting with `honorarios_laudo_examen_` MUST resolve to `tipo='laudos'` with a description derived from `Examen::find()` or a fallback `ucfirst(str_replace('_', ' ', $tipoKey))`.

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | `normalizeGastos` with `honorarios_laudo_examen_123` → `tipo='laudos'` | PHPUnit Feature test with RefreshDatabase; create an Examen, pass gasto key, assert normalized output |
| Unit | `normalizeGastos` with old keys (`honorarios_laudos_egg`) → unchanged | Assert existing keys still match `$tipoMap` |
| Unit | `normalizeGastos` with unknown key → `tipo='extra'` fallback | Assert fallback behavior preserved |
| Integration | create repase with dynamic laudo → persists correctly | POST with `gastos[honorarios_laudo_examen_{id}]`, assert gasto row in DB |

## Migration / Rollout

No database migration required. The `gasto_key` column already stores arbitrary strings. Old repases with the 3 legacy keys continue working because those keys remain in `$tipoMap`. New repases generate ID-based keys. Rollback is trivial: revert the 4 file changes — no data migration to unwind.

## Open Questions

- [ ] Should deactivated exams (`is_active=false`) be excluded from the laudo field loop? Current design shows all active exams. Deactivated exam repases will have stale gasto data (data preserved, field not rendered on edit — acceptable per exploration.md risk assessment).
- [ ] Should `Examen::find()` call in `normalizeGastos` be cached or eager-loaded? With small N (typical exam count < 50), single-query per laudo is acceptable. If perf becomes an issue, preload all exams into an in-memory map.
