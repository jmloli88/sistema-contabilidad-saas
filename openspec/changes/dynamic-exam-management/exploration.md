## Exploration: Dynamic Exam Management

### Current State

#### Examen Model & Database Schema

The `examenes` table (created by `2026_03_04_130603_create_examenes_table.php`) has these columns:

| Column | Type | Notes |
|--------|------|-------|
| `id` | BIGINT UNSIGNED PK | Auto-increment |
| `empresa_id` | BIGINT UNSIGNED FK NOT NULL | Added later via two migrations — first nullable, then made NOT NULL with `cascadeOnDelete` |
| `nombre` | VARCHAR(255) | Indexed |
| `precio_sin_nota` | DECIMAL(10,2) | Required, global price |
| `precio_con_nota` | DECIMAL(10,2) | Required, must be > precio_sin_nota |
| `created_at` / `updated_at` | TIMESTAMP | Standard Laravel |

**Key observations:**
- The table has NO `is_active` column, NO `deleted_at` (soft delete), and NO `activo` flag.
- The CHECK constraint (`precio_sin_nota < precio_con_nota`) only applies to MySQL; SQLite skips it.
- Exams are multi-tenant scoped via `empresa_id` with `cascadeOnDelete` — deleting an empresa cascades.

#### Examen Model (`app/Models/Examen.php`)

- Uses traits: `HasFactory`, `ScopedByEmpresa`
- `$fillable`: `nombre`, `precio_sin_nota`, `precio_con_nota`, `empresa_id`
- Has a `belongsToMany(Clinica::class, 'clinica_examen')` with pivot prices (from the **per-clinic-exam-prices** feature)
- Has `getPrecioParaClinica(?int $clinicaId, string $tipoPrecio): float` — two-tier resolution: clinic override → global fallback
- Has extensive analytical scopes: `scopeForUtilizationAnalysis`, `scopeUtilizationStats`, `scopeUtilizationTrends`, `scopePopularityByClinic`, `scopeProfitabilityAnalysis`, `scopeLowUtilization`, `scopeWithValidData`
- Has helper methods: `calculateUtilizationStats()`, `calculateUtilizationTrend()`, `getPopularityRanking()`, `detectUtilizationAnomalies()`

#### ExamenController (`app/Http/Controllers/ExamenController.php`)

The controller currently has only **3 methods** — it is a **price management controller**, not a full CRUD controller:

| Method | Route | Purpose |
|--------|-------|---------|
| `index()` | `GET /examenes` | Lists all exams with override counts, ordered by name |
| `edit(Examen $examen)` | `GET /examenes/{examen}/edit` | Shows form to edit global prices + per-clinic overrides |
| `update(Request, Examen $examen)` | `PUT /examenes/{examen}` | Saves global prices + per-clinic overrides |

**Missing CRUD operations:** There is NO `create()`, `store()`, or `destroy()` method. Exams can only be seeded, not added or removed via the UI.

#### Routes (`routes/web.php`)

All exam routes are under the `auth + admin + empresa.scope` middleware group:

```php
Route::get('/examenes', [ExamenController::class, 'index'])->name('examenes.index');
Route::get('/examenes/{examen}/edit', [ExamenController::class, 'edit'])->name('examenes.edit');
Route::put('/examenes/{examen}', [ExamenController::class, 'update'])->name('examenes.update');
```

No create/store/destroy routes exist.

#### Examen Seeder (`database/seeders/ExamenSeeder.php`)

Seeds exactly 7 predefined exams, all linked to a single "Default Seed Empresa":
1. Electroencefalograma — R$100.00 / R$120.00
2. Electroencefalograma c/ mapa — R$120.00 / R$140.00
3. Electroencefalograma c/ mapeamento 3d + foto estimulo — R$200.00 / R$220.00
4. Electroneuromiografia FACIAL unilateral — R$170.00 / R$200.00
5. Electroneuromiografia MEMBROS unilateral — R$150.00 / R$180.00
6. Potencial evocado AUDITIVO unilateral — R$146.00 / R$166.00
7. Potencial evocado VISUAL unilateral — R$146.00 / R$166.00

The seeder creates these by calling `Examen::create()` inside a foreach. It uses `Empresa::firstOrCreate(['nombre' => 'Default Seed Empresa'])` — meaning it does NOT seed exams per-empresa; it creates a single shared empresa for all seeded data.

#### Examen Factory (`database/factories/ExamenFactory.php`)

Generates random exam names and prices (sin_nota 80-180, con_nota 15-40 higher). Creates an `Empresa::factory()` for `empresa_id` by default.

#### Views

**`resources/views/examenes/index.blade.php`** — lists all exams in a table (desktop) and cards (mobile). Shows name, prices, override badge, and an "edit prices" link. **No create button, no delete button, no activate/deactivate toggle.**

**`resources/views/examenes/edit.blade.php`** — edit form for global prices (precio_sin_nota, precio_con_nota) + an optional per-clinic pricing section. **No name field, no active toggle, no delete button.**

**`resources/views/repases/create.blade.php`** — passes `$examenes` via `json_encode()` into Alpine.js `repaseForm()`. The frontend iterates `examenesDisponibles` (all exams). **No active/inactive filtering.**

**`resources/views/repases/edit.blade.php`** — identical pattern: all exams loaded into Alpine, no filtering.

### Affected Areas

| File | Why It's Affected |
|------|-------------------|
| `database/migrations/2026_03_04_130603_create_examenes_table.php` | Existing schema — no `is_active` column |
| **New migration** | MUST add `is_active` boolean (default true) to `examenes` |
| `app/Models/Examen.php` | MUST add `is_active` to `$fillable` and `$casts`; add scope `scopeActive()` |
| `app/Models/Empresa.php` | Already has `examenes(): HasMany` — no change needed |
| `app/Http/Controllers/ExamenController.php` | MUST add `create()`, `store()`, `destroy()` methods; update `index()` for active filter |
| **New controller or existing** | Routes for `POST /examenes` (store) and `DELETE /examenes/{examen}` (destroy) |
| `routes/web.php` | MUST add 3 new routes: create, store, destroy |
| `database/seeders/ExamenSeeder.php` | MUST change to seed per-empresa (each empresa gets the 7 defaults) |
| `database/seeders/DatabaseSeeder.php` | May need reordering if ExamenSeeder becomes dependent on Empresa seed |
| `database/factories/ExamenFactory.php` | Should generate `is_active: true` by default |
| `resources/views/examenes/index.blade.php` | MUST add "create" button, "activate/deactivate" toggle per row, maybe "delete" button |
| **New view: `examenes/create.blade.php`** | MUST create a form to add a new exam (name + prices) |
| `resources/views/examenes/edit.blade.php` | SHOULD add `is_active` toggle (could be here instead of index) |
| `resources/views/repases/create.blade.php` | MUST filter `$examenes` to active exams before passing to Alpine |
| `resources/views/repases/edit.blade.php` | MUST filter `$examenes` to active exams before passing to Alpine |
| `app/Http/Controllers/RepaseController.php` | Lines 80-82 and 184-186: MUST add `->active()` scope to Examen queries |
| `app/Services/RepaseService.php` | Lines 79, 185, 243: `Examen::findOrFail()` — these look up by ID, so a disabled exam already linked to a historical repase will still resolve. **This is correct behavior** (historical records should still display). No change needed. |
| `app/Services/Reportes/ReporteService.php` | Lines 122-128, 139-157, 217-219: May need to preserve inactive exams in reports for historical accuracy — depends on spec |
| `app/Http/Controllers/ReporteController.php` | Line 271: `Examen::orderBy('nombre')` — may need active/inactive filter |
| `tests/Feature/ExamenPrecioClinicaTest.php` | Factory creates exams without `is_active` — add default or migration-aware setup |
| `tests/Unit/Models/ExamenPriceResolverTest.php` | Same |
| `app/Exports/RentabilidadExamenExport.php` | Check if it needs active/inactive awareness |
| `app/Models/Clinica.php` | Already has `belongsToMany(Examen::class)` — no change needed |
| `app/Models/RepaseExamen.php` | Already has `belongsTo(Examen::class)` — no change needed |
| Empresa creation flow | When a new empresa is created, the 7 default exams MUST be auto-seeded |

### Per-Clinic-Exam-Prices Feature Interaction

The **per-clinic-exam-prices** feature (already implemented) adds a `clinica_examen` pivot table with per-clinic price overrides. This interacts with dynamic exam management in these ways:

1. **When disabling an exam**: Per-clinic price overrides for that exam should logically be preserved but ignored for new repases (since the exam won't appear in the active list). The pivot data can remain — it's harmless.
2. **When deleting an exam**: The `cascadeOnDelete` FK on `clinica_examen.examen_id` will auto-clean orphan pivots. BUT deletion should be blocked if the exam has historical `repase_examenes` records (data integrity).
3. **Active scope**: `getPrecioParaClinica()` doesn't need changes — it's called by `RepaseService` only for exams that are already in the form data (quantity > 0). If the exam is disabled, it won't appear in the create/edit form dropdown, so this is moot for new repases.
4. **Override management UI**: The existing exams edit page already manages per-clinic overrides. If an exam is disabled, the overrides should still be visible (for admin reference) but ideally marked as "inactive".

### Current Soft-Delete / is_active Pattern

**Neither exists on the Examen model.** The system uses:
- **SoftDeletes** on `Repase` model (`app/Models/Repase.php`) — repases use `SoftDeletes`
- **No soft deletes** on `Examen`, `Clinica`, or other tenant models
- **No `is_active`** pattern anywhere in the codebase

The `ScopedByEmpresa` trait (`app/Models/Traits/ScopedByEmpresa.php`) is the only scoping pattern — it uses a global scope to filter by `empresa_id` when `EmpresaContext::isSet()`.

### Empresa Scoping (Multi-Tenant)

Already fully implemented for `Examen`:
- `Examen` model uses the `ScopedByEmpresa` trait → a global scope adds `WHERE examenes.empresa_id = ?` automatically
- `EmpresaContext` is set by middleware for tenant routes, cleared for SaaS admin routes
- `empresa_id` is NOT NULL with `cascadeOnDelete` — deleting an empresa deletes its exams
- The `ExamenSeeder` currently seeds to a single "Default Seed Empresa" — this MUST change for dynamic management

The critical implication: **each empresa should have its own set of 7 default exams**. When a new empresa registers, the system should auto-create the 7 defaults for that empresa.

### How Repase Forms Load Exams

**RepaseController::create()** (lines 79-82):
```php
$examenes = Examen::select('id', 'nombre', 'precio_sin_nota', 'precio_con_nota')
    ->orderBy('nombre')
    ->get();
```

**RepaseController::edit()** (lines 183-186):
```php
$examenes = Examen::select('id', 'nombre', 'precio_sin_nota', 'precio_con_nota')
    ->orderBy('nombre')
    ->get();
```

Both load ALL exams with no active/inactive filter. The data is `json_encode`d into Alpine.js `x-data` and iterated in `examenesDisponibles` for the exam selection grid.

**Note**: Both also do a SECOND query (`Examen::with('clinicas')->get()`) to build the `$preciosPorClinica` map — this would also benefit from an active scope, but inactive exams with clinic price overrides might still need to appear here.

### Approaches

#### Approach 1: `is_active` boolean flag (Recommended)

Add a simple `is_active` boolean column (default `true`) to the `examenes` table. Add CRUD routes and controller methods. Add an `scopeActive()` that filters `is_active = true`. The repase forms query `->active()`.

**Details:**
- New migration: `ALTER TABLE examenes ADD COLUMN is_active TINYINT(1) NOT NULL DEFAULT 1 AFTER precio_con_nota`
- Add `is_active` to `$fillable` and cast it to `boolean` in `Examen` model
- Add `scopeActive()` on `Examen`: `where('is_active', true)`
- Update `RepaseController::create()` and `edit()`: `Examen::select(...)->active()->orderBy('nombre')->get()`
- Add `ExamenController::create()` → return create view
- Add `ExamenController::store()` → validate + persist
- Add `ExamenController::destroy()` → soft-block or hard-delete (or just toggle inactive)
- Add routes: `GET /examenes/create`, `POST /examenes`, `DELETE /examenes/{examen}`
- Add a toggle route or use the edit form for `is_active`
- Update `ExamenSeeder` to seed per-empresa

| Pros | Cons | Effort |
|------|------|--------|
| Simple, reversible, no data loss | Doesn't handle "why disabled?" (audit reason) | Low |
| Frontend filtering is trivial | Soft-disable still allows lookups by ID (historical) | |
| Compatible with existing per-clinic pricing | | |
| Matches the user requirement exactly ("soft disable without deleting") | | |

#### Approach 2: Soft Deletes (`SoftDeletes` trait)

Add Laravel's `SoftDeletes` trait to `Examen` model, add `deleted_at` column. A "disabled" exam has a non-null `deleted_at`.

**Details:**
- New migration: `ALTER TABLE examenes ADD COLUMN deleted_at TIMESTAMP NULL`
- Add `SoftDeletes` trait to `Examen`
- Add routes for soft-delete (destroy)
- Repase forms use `Examen::withoutTrashed()->get()`
- Create uses the standard store flow

| Pros | Cons | Effort |
|------|------|--------|
| Built-in Laravel support, `withTrashed()` for recovery | Harder to re-enable (need to restore instead of toggle) | Low |
| Standard pattern many devs know | `SoftDeletes` filters ALL queries by default — the analytical scopes in `Examen.php` that JOIN on repase_examenes would need `withTrashed()` to include historical data | |
| Cascade delete, FK integrity | User specifically asked for "activate/deactivate" not "delete/restore" — different mental model | |

#### Approach 3: `activo` boolean + `deleted_at` (Hybrid)

Use `is_active` for soft-disable and `SoftDeletes` only for actual deletion (when safe). This gives maximum flexibility.

| Pros | Cons | Effort |
|------|------|--------|
| Both patterns available | Over-engineered for the current requirement | Medium |
| Future-proof | Two mechanisms for similar concerns | |

### Recommendation

**Approach 1 (`is_active` boolean)** is the clear winner given the user's stated requirements:

> "Ability to activate/deactivate exams (soft disable without deleting)"

This maps 1:1 to an `is_active` boolean. The user explicitly wants activate/deactivate, NOT delete/restore. It's simpler, more intuitive, and avoids the complexity of `SoftDeletes` interfering with analytical scopes that need to reference ALL exams for historical reporting.

The deletion case (hard delete) should be handled separately — only allowed when the exam has ZERO historical `repase_examenes` records. This prevents orphaned references in reports.

### Seed Strategy Change

The current `ExamenSeeder` strategy is broken for multi-tenant: it creates one "Default Seed Empresa" and assigns all 7 exams to it. This MUST change to one of:

1. **Event-based seeding**: Listen for `Empresa::created` and auto-generate the 7 default exams for the new empresa.
2. **Seeder that iterates all empresas**: `ExamenSeeder` reads all empresas and creates 7 exams for any that don't have them yet.
3. **Empresa factory with afterCreate**: If using factories for tests, the factory should create default exams.

**Recommended**: Option 1 (event-based) is cleanest. A `\App\Listeners\SeedDefaultExams` listener on the `created` event of `Empresa` model. This fires both for SaaS admin UI creation and for test factories (unless using `Event::fake()`).

### Risks

1. **Analytical scopes and historical data**: The `Examen` model has many `scope*` methods that do `leftJoin('repase_examenes')`. These should include inactive exams when querying historical data, but exclude them when presenting "available exams" lists. The `scopeActive()` must be explicit — NOT forced globally (no global scope for active).

2. **Per-clinic price overrides on inactive exams**: If a clinic had custom prices for an exam that's now disabled, the pivot data remains. The `getPrecioParaClinica()` method doesn't check `is_active` — this is correct because it's used by `RepaseService` for existing repase records. But the clinic overrides editor should visually indicate when an exam is inactive.

3. **Seed data migration**: Existing production databases have the 7 exams linked to a "Default Seed Empresa". When enabling dynamic management, those exams will now be manageable. The migration to add `is_active` MUST set `is_active = true` for all existing records (the default handles this).

4. **Empresa creation flow**: Currently, creating an empresa in the SaaS admin panel does NOT seed default exams. After this change, a listener SHOULD seed the 7 defaults. This needs to be idempotent (don't double-seed if the listener fires multiple times).

5. **Form frontend complexity**: The Alpine.js `repaseForm()` already handles exam list dynamically. Changing from "all exams" to "active exams" requires only changing the PHP query — the frontend works automatically because it renders whatever `$examenes` contains. Low risk.

6. **Deleting vs deactivating**: If hard-delete is implemented (approach 1 allows it for exams with no history), the FK cascade from `clinica_examen` and `repase_examenes` could cause data loss. The controller MUST check `$examen->repaseExamenes()->exists()` before allowing delete. For exams with history, only soft-disable (set `is_active = false`) should be allowed.

### Readiness for Proposal

**Yes** — the exploration is complete. The orchestrator should tell the user:

- **Recommended approach**: `is_active` boolean column + new CRUD routes/controller methods + scope per-empresa seeding.
- **Main effort**: Backend (migration, controller, model scope, routes) is straightforward. Frontend (create exam form, activate/deactivate toggles in index view, active filter in repase forms) is moderate.
- **No existing data migration risk**: All existing exams get `is_active = true` by default.
- **Key decision needed**: Should "deleting" an exam with zero history hard-delete it, or always just deactivate? **Recommendation**: Always deactivate. If hard-delete is needed, add it later.
- **Per-empresa seeding**: When a new empresa registers, the 7 default exams should be auto-created.
- **Interaction with per-clinic pricing**: Inactive exams' clinic price overrides are preserved but invisible in repase forms — safe.
- **Next SDD phase**: `sdd-propose` for formal proposal and scope definition.
