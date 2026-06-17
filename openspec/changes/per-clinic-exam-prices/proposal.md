# Proposal: Per-Clinic Exam Prices with Global Fallback

## Intent

Enable clinics to set their own exam prices while keeping global defaults as fallback. Currently `examenes.precio_sin_nota` and `examenes.precio_con_nota` apply uniformly — clinics cannot differentiate rates.

## Scope

### In Scope
- Pivot table `clinica_examen(clinica_id, examen_id, precio_sin_nota?, precio_con_nota?)` with nullable DECIMAL(10,2) columns
- `belongsToMany` relationships on `Examen` and `Clinica` with pivot columns
- `Examen::getPrecioParaClinica($clinicaId, $tipoPrecio)` — checks pivot, falls back to global
- Update `RepaseService` 3 price-lookup points (createRepase L81-83, updateRepase L185-187, calculateTotalExamenes L242-244) to accept `$clinicaId`
- Collapsible "Precios por Clínica" section in exam edit view with per-clinic override inputs
- Index view indicator showing override count per exam
- Migration, controller sync logic, tests (unit + feature)

### Out of Scope
- Modifying analytical scopes (`scopeProfitabilityAnalysis`, `scopeUtilizationStats`) — remain global-price baselines
- Recalculating historical `repase_examenes.precio_unitario_usado` — already snapshotted
- Excel/PDF export per-clinic price breakdowns
- Alpine.js repase form price reload on clinic change (deferred)

## Capabilities

### New Capabilities
- `exam-pricing`: Two-tier price resolution (per-clinic override → global fallback) for exam prices, including model relationships, resolver method, and UI management

### Modified Capabilities
None — no existing specs to modify.

## Approach

**Schema**: Pivot table `clinica_examen` with composite PK `(clinica_id, examen_id)`. Nullable prices mean "use global."

**Resolver**:
1. `getPrecioParaClinica($clinicaId, $tipoPrecio)` queries pivot for `(clinica_id, examen_id)` with non-null `precio_$tipoPrecio`
2. Returns pivot value if found, else `$this->{"precio_$tipoPrecio"}` (global)

**Service**: Replace all 3 `$examen->precio_sin_nota` / `$examen->precio_con_nota` reads in `RepaseService` with `$examen->getPrecioParaClinica($clinicaId, $tipoPrecio)`.

**UI**: Edit view adds `<details>` collapsible section below global inputs. Table: clinic name + two numeric inputs per row. Empty = NULL (use global). Controller syncs pivot on save.

**Scopes**: Analytical scopes reference `examenes.precio_*` directly in SQL — intentionally kept as global baselines.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `database/migrations/` | New | `create_clinica_examen_table` |
| `app/Models/Examen.php` | Modified | Add `clinicas()` relationship, `getPrecioParaClinica()` |
| `app/Models/Clinica.php` | Modified | Add `examenes()` relationship |
| `app/Services/RepaseService.php` | Modified | Inject `$clinicaId` into 3 price lookups |
| `app/Http/Controllers/ExamenController.php` | Modified | Pivot sync in store/update |
| `resources/views/examenes/edit.blade.php` | Modified | Collapsible per-clinic section |
| `resources/views/examenes/index.blade.php` | Modified | Override count badge |
| `tests/` | Modified | Price assertions, pivot tests |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| CHECK constraint wrongly applied to pivot columns | Low | Pivot columns are separate table; no CHECK in migration |
| Scope SQL remains hardcoded to examenes table | Low | Scopes intentionally use global baselines — documented decision |
| Existing tests break on model relationship changes | Medium | Update factories; run full suite before merging |

## Rollback Plan

1. Drop `clinica_examen` table (migration rollback)
2. Remove `clinicas()` from `Examen`, `examenes()` from `Clinica`
3. Revert `RepaseService` 3 lookups to direct property access
4. Remove per-clinic UI section from edit/index views
Historical `repase_examenes.precio_unitario_usado` is unaffected — zero data risk.

## Dependencies

None — standalone change.

## Success Criteria

- [ ] Exam edit view saves per-clinic overrides and reloads them correctly
- [ ] Repase create/update resolves clinic price (override or global fallback)
- [ ] Null pivot prices correctly fall back to `examenes.precio_*`
- [ ] All existing 46 PHPUnit tests pass after model/service updates
- [ ] New tests: resolver (override, fallback, missing pivot), controller store/update with clinic prices
