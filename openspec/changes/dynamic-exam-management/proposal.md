# Proposal: Dynamic Exam Management

## Intent

The system hardcodes 7 exams seeded to a single "Default Seed Empresa." Admins cannot add, remove, or disable exams. This change enables per-empresa exam lifecycle management — create, edit, activate/deactivate — while preserving historical repase data for inactive exams.

## Scope

### In Scope
- `is_active` boolean column on `examenes` (default `true`)
- Exam CRUD: create, edit name/prices, soft-disable (`is_active` toggle), hard-delete (only exams with zero history)
- Repase forms filter to active exams
- Per-empresa auto-seeding of 7 defaults on empresa creation
- Existing exams get `is_active = true` — no data loss

### Out of Scope
- Deactivation audit trail (why disabled?)
- Bulk activate/deactivate
- Exam templates or categories
- Pricing tiers beyond existing global + per-clinic overrides

## Capabilities

### New Capabilities
- `exam-management`: Full lifecycle CRUD — create, edit name/prices, activate/deactivate toggle, conditional hard-delete. Active-only filtering in repase forms and exam listings.
- `exam-default-seeding`: Event-driven auto-creation of 7 default exams per new empresa. Idempotent (no double-seeding).

### Modified Capabilities
None — no existing spec-level capability definitions exist.

## Approach

Add `is_active` TINYINT(1) DEFAULT 1 to `examenes` via new migration. Maps 1:1 to "activate/deactivate" — simpler than SoftDeletes, avoids global-scope interference with analytical queries needing ALL exams for historical reporting.

**Flow**: Admin manages exams via new CRUD forms → Repase forms query `Examen::active()` → Inactive exams' repase records remain resolvable by ID (RepaseService uses `findOrFail`, unaffected).

**Seeding**: Eloquent `created` event listener on `Empresa` auto-generates 7 defaults. Listener checks `$empresa->examenes()->count() === 0` before seeding.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `examenes` table | New column | `is_active` TINYINT(1) DEFAULT 1 |
| `app/Models/Examen.php` | Modified | `is_active` fillable/cast + `scopeActive()` |
| `app/Http/Controllers/ExamenController.php` | Modified | Add create, store, destroy, toggle-active |
| `routes/web.php` | Modified | 4 new exam routes |
| `resources/views/examenes/` | New + Modified | create.blade.php (new), index/edit updated with CRUD controls |
| `resources/views/repases/` | Modified | Filter exams to active in create/edit |
| `app/Http/Controllers/RepaseController.php` | Modified | Add `->active()` to exam queries |
| `app/Models/Empresa.php` | Modified | `created` event → seed 7 defaults |
| `database/seeders/ExamenSeeder.php` | Modified | Per-empresa seeding strategy |
| `database/factories/ExamenFactory.php` | Modified | Default `is_active: true` |
| `tests/**` | Modified | Factories updated for `is_active` |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Analytical scopes inadvertently exclude inactive exams from historical reports | Low | `scopeActive()` is explicit, not a global scope. Historical JOINs unchanged. |
| Double-seeding defaults on empresa creation | Low | Listener checks exam count == 0 before seeding |
| Hard-delete causes orphaned repase_examenes references | Low | Controller blocks delete if `repaseExamenes()->exists()` |
| Per-clinic price overrides visible on inactive exams | Low | Override editor shows inactive badge; data preserved, harmless |

## Rollback Plan

1. Remove new migration from `migrations` table; column is additive — MySQL ignores extra columns if rolled back
2. Delete 4 new routes from `web.php`
3. Revert `ExamenController` to 3-method state
4. Remove event listener from `EventServiceProvider`
5. Revert repase query changes (remove `->active()`)
6. Inactive exams become visible everywhere again — zero data loss

## Dependencies

- `per-clinic-exam-prices` (already implemented) — interacts via `scopeActive()` but no changes needed
- `Empresa` model — must support Eloquent events (already does)

## Success Criteria

- [ ] Admin can create exam with name, precio_sin_nota, precio_con_nota
- [ ] Admin can deactivate exam; disappears from repase create/edit forms
- [ ] Admin can reactivate a deactivated exam
- [ ] Historical repases display deactivated exams correctly
- [ ] New empresa gets exactly 7 default exams on creation
- [ ] Repase forms only show active exams in selection grid
- [ ] Hard-delete blocked for exams with historical repase records
- [ ] All existing tests pass with updated factories
