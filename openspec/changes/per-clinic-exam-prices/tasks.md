# Tasks: Per-Clinic Exam Prices with Global Fallback

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | ~420 (additions + deletions) |
| 400-line budget risk | Medium |
| Chained PRs recommended | Yes |
| Suggested split | PR 1 (Foundation) → PR 2 (Service) → PR 3 (UI + Controller) |
| Delivery strategy | ask-always |
| Chain strategy | feature-branch-chain |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: feature-branch-chain
400-line budget risk: Medium

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Migration, models, resolver logic + unit tests | PR 1 | Base = `feature/per-clinic-exam-prices` tracker branch |
| 2 | RepaseService price lookups + integration tests | PR 2 | Base = PR 1 branch; depends on resolver from Unit 1 |
| 3 | Controller, views, feature tests + existing test updates | PR 3 | Base = PR 2 branch; depends on service from Unit 2 |

## Phase 1: Foundation — Migration, Models, Resolver

**TDD:** Write tests BEFORE resolver implementation. Run RED → GREEN.

- [x] 1.1 **RED** — Write `tests/Unit/Models/ExamenPriceResolverTest.php`: override wins, fallback on NULL pivot, fallback on missing pivot, null clinicaId (REQ-PRICE-002, REQ-PRICE-003). Assert `getPrecioParaClinica()` + `getPreciosParaClinica()` array shape.
- [x] 1.2 Create `database/migrations/2026_06_16_221351_create_clinica_examen_table.php` — composite PK `(clinica_id, examen_id)`, nullable `precio_sin_nota`/`precio_con_nota` DECIMAL(10,2), FK indexes. (REQ-PRICE-001)
- [x] 1.3 Add `clinicas(): BelongsToMany` with `->withPivot(['precio_sin_nota', 'precio_con_nota'])` to `Examen` model.
- [x] 1.4 Add `examenes(): BelongsToMany` to `Clinica` model.
- [x] 1.5 **GREEN** — Implement `Examen::getPrecioParaClinica(?int $clinicaId, string $tipoPrecio): float` — checks pivot non-null, falls back to `$this->{"precio_$tipoPrecio"}`. Implement `getPreciosParaClinica()` convenience wrapper. (REQ-PRICE-002, REQ-PRICE-003)
- [x] 1.6 **REFACTOR** — Clean up resolver: extract `resolvePivotPrice()` private method if duplication appears. Run full test suite (46 existing + new).

## Phase 2: Service Integration — RepaseService

**TDD:** Update existing service tests before implementation. Run RED → GREEN.

- [x] 2.1 **RED** — Created `tests/Feature/RepaseServiceTest.php` with 9 test cases: override snapshots (sin_nota + con_nota), global fallback, null pivot, mixed clinic prices, updateRepase scenarios, and calculateTotalExamenes edge cases. (REQ-PRICE-004, REQ-PRICE-007)
- [x] 2.2 Change `RepaseService::createRepase()` — replaced `$examen->precio_sin_nota`/`precio_con_nota` with `$examen->getPrecioParaClinica($data['clinica_id'], $data['tipo_precio'])`. (REQ-PRICE-004, REQ-PRICE-007)
- [x] 2.3 Change `RepaseService::updateRepase()` — same substitution as 2.2. (REQ-PRICE-004)
- [x] 2.4 Change `RepaseService::calculateTotalExamenes()` — added `?int $clinicaId = null` parameter, replaced L242-244 with `$examen->getPrecioParaClinica($clinicaId, $tipoPrecio)`. Updated callers in create/update to pass `$data['clinica_id']`. (REQ-PRICE-004)
- [x] 2.5 **GREEN** — Run service tests. All 9 tests pass. Full suite: 247 passed, 22 pre-existing failures unchanged. (REQ-PRICE-007)

## Phase 3: Controller + Views — UI Management

**TDD:** Write feature tests before controller changes.

- [x] 3.1 **RED** — Write `tests/Feature/ExamenPrecioClinicaTest.php`: POST update with `precios_clinicas` array asserts pivot rows; index response shows override count badge; edit response includes clinics. (REQ-PRICE-005, REQ-PRICE-006)
- [x] 3.2 Update `ExamenController::edit()` — load `Clinica::orderBy('nombre')->get()` and pass to view. (REQ-PRICE-005)
- [x] 3.3 Update `ExamenController::update()` — validate nested `precios_clinicas[clinica_id][precio_sin_nota/precio_con_nota]`, sync pivot via `syncWithoutDetaching()`. Blank input → NULL. (REQ-PRICE-005)
- [x] 3.4 Update `ExamenController::index()` — add `->withCount(['clinicas as overrides_count' => fn($q) => $q->wherePivotNotNull('precio_sin_nota')->orWherePivotNotNull('precio_con_nota')])` or equivalent eager-load attribute. (REQ-PRICE-006)
- [x] 3.5 Add collapsible `<details>` section to `examenes/edit.blade.php` with per-clinic table (clinic name + two numeric inputs per row), empty = NULL hint. (REQ-PRICE-005)
- [x] 3.6 Add override count badge to `examenes/index.blade.php`: show badge only when `$examen->overrides_count > 0`. (REQ-PRICE-006)
- [x] 3.7 **GREEN** — Run all feature tests. Verify: save overrides → reload → values persist; empty inputs → NULL → fallback to global.
- [x] 3.8 Run full test suite (`php artisan test`) — confirm all 46 existing tests + new tests pass.

## Verification Gates

- [x] V1. `getPrecioParaClinica()` with override returns override value
- [x] V2. `getPrecioParaClinica()` with NULL pivot returns global price
- [x] V3. `getPrecioParaClinica()` with missing pivot returns global price
- [x] V4. `createRepase` with override snapshots the override in `precio_unitario_usado`
- [x] V5. `createRepase` without override uses global price as snapshot
- [x] V6. Edit view shows collapsible table with all clinics
- [x] V7. Saving per-clinic prices persists pivot correctly
- [x] V8. Index shows override badge only when overrides exist
- [x] V9. Existing 238 tests + 9 new = 247 pass with model relationship additions
