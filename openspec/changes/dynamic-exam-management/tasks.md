# Tasks: Dynamic Exam Management

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 320–380 |
| 400-line budget risk | Low |
| Chained PRs recommended | No |
| Suggested split | Single PR |
| Delivery strategy | single-pr |
| Chain strategy | pending |

Decision needed before apply: No
Chained PRs recommended: No
Chain strategy: pending
400-line budget risk: Low

### Suggested Work Units

| Unit | Goal | Likely PR | Notes |
|------|------|-----------|-------|
| 1 | Full change: foundation + CRUD + views + seeding + tests | PR 1 | main branch; single coherent PR under 400 lines |

## Phase 1: Foundation (Migration + Model)

- [x] 1.1 [RED] Test: `scopeActive()` returns only exams where `is_active = true`
- [x] 1.2 [GREEN] Migration: add `is_active` boolean (default true) to `examenes` after `precio_con_nota`
- [x] 1.3 [GREEN] Update `Examen` model: add `is_active` to `$fillable` + `$casts` (boolean); add `scopeActive()`; add static `defaults(): array` returning the 7 standard exams
- [x] 1.4 [REFACTOR] Update `ExamenFactory`: add `'is_active' => true` to definition

## Phase 2: Backend CRUD (Controller + Routes)

- [x] 2.1 [RED–GREEN] Write test + implement `ExamenController@create()`, `@store(Request)`: validate nombre (unique per empresa), precio_sin_nota < precio_con_nota
- [x] 2.2 [RED–GREEN] Write test + implement `ExamenController@toggleActive(Examen)`: flips `is_active`
- [x] 2.3 [RED–GREEN] Write test + implement `ExamenController@destroy(Examen)`: return 409 if `repaseExamenes()->exists()`, else hard-delete
- [x] 2.4 [GREEN] Register 4 routes in `web.php` under admin middleware group: `create`, `store`, `toggle`, `destroy`

## Phase 3: Repase Form Filtering

- [x] 3.1 [RED] Test: repase create/edit form excludes inactive exams from selection grid
- [x] 3.2 [GREEN] Update `RepaseController@create()` and `@edit()`: add `->active()` to both `Examen::` queries

## Phase 4: Frontend Views

- [x] 4.1 Create `resources/views/examenes/create.blade.php`: form for nombre, precio_sin_nota, precio_con_nota
- [x] 4.2 Update `resources/views/examenes/index.blade.php`: add "Nuevo Examen" button, activate/deactivate toggle per row, delete button (hidden if has repase history), active/inactive badge
- [x] 4.3 Update `resources/views/examenes/edit.blade.php`: add editable `nombre` field

## Phase 5: Auto-Seeding

- [x] 5.1 [RED] Test: new empresa auto-creates exactly 7 default exams; re-dispatch does not duplicate
- [x] 5.2 [GREEN] Update `ExamenSeeder`: iterate all empresas, use `defaults()` to create missing exams
- [x] 5.3 [GREEN] Add `Empresa::created(fn)` listener in `AppServiceProvider::boot()`: seed 7 defaults if `$empresa->examenes()->count() === 0`

## Phase 6: Final Verification

- [x] 6.1 Run `php artisan test`; fix any regressions
- [x] 6.2 Manually verify all spec scenarios from `exam-management/spec.md` and `exam-default-seeding/spec.md`

### Verification Results

| Scenario | Status |
|----------|--------|
| Create a new exam | ✅ Tested via `ExamenCrudTest::test_admin_can_store_new_exam()` |
| Edit an existing exam | ✅ Update endpoint works (existing `ExamenPrecioClinicaTest` passes) |
| Deactivate an active exam | ✅ Tested via `ExamenCrudTest::test_admin_can_toggle_exam_to_inactive()` |
| Reactivate a deactivated exam | ✅ Tested via `ExamenCrudTest::test_admin_can_toggle_exam_back_to_active()` |
| Historical repase displays deactivated exam | ✅ Tested via `RepaseFormExamenFilterTest::test_repase_detail_shows_inactive_exam_name()` |
| Repase create form filters active exams | ✅ Tested via `RepaseFormExamenFilterTest::test_repase_create_form_only_shows_active_exams()` |
| Repase edit form filters active exams | ✅ Tested via `RepaseFormExamenFilterTest::test_repase_edit_form_shows_only_active_exams()` |
| Hard-delete exam with no history | ✅ Tested via `ExamenCrudTest::test_admin_can_delete_exam_without_history()` |
| Block deletion of exam with history | ✅ Tested via `ExamenCrudTest::test_cannot_delete_exam_with_repase_history()` |
| New empresa receives default exams | ✅ Tested via `ExamenAutoSeedingTest::test_new_empresa_creates_exactly_7_default_exams()` |
| Second trigger does not duplicate exams | ✅ Tested via `ExamenAutoSeedingTest::test_empresa_with_7_exams_does_not_get_additional_exams()` |
| scopeActive() returns active exams only | ✅ Tested via `ExamenScopeActiveTest::test_scope_active_returns_only_active_exams()` |

**Full suite**: 370 passed, 20 failed (all pre-existing, none caused by this change)
