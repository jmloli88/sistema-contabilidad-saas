## Exploration: Per-Clinic Exam Prices

### Current State

Exam prices are stored as **global columns** on the `examenes` table. Two decimal columns (`precio_sin_nota`, `precio_con_nota`) hold prices that apply to ALL clinics. There is **no existing relationship** between `clinicas` and `examenes` — no pivot, no junction table, no JSON column, nothing.

When a repase (medical service report) is created or updated, the `RepaseService` resolves prices by:
1. Looking up the `Examen` model directly
2. Reading `$examen->precio_sin_nota` or `$examen->precio_con_nota` based on `tipo_precio` (sin_nota / con_nota)
3. Storing that resolved price in `repase_examenes.precio_unitario_usado` at creation time (snapshot)

### Current Database Schema for examenes

```sql
CREATE TABLE examenes (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(255) NOT NULL,
    precio_sin_nota DECIMAL(10, 2) NOT NULL,
    precio_con_nota DECIMAL(10, 2) NOT NULL,
    created_at      TIMESTAMP NULL,
    updated_at      TIMESTAMP NULL,
    INDEX idx_nombre (nombre),
    CONSTRAINT chk_precio_sin_nota_menor CHECK (precio_sin_nota < precio_con_nota) -- MySQL only
);
```

### Current Flow: How Repase Form Resolves Exam Prices

1. **Controller** (`RepaseController::create/edit`): Loads `Examen::orderBy('nombre')->get()` and passes `$examenes` to the view
2. **View** (`repases/create.blade.php` line 60): Serializes `$examenes` via `json_encode($examenes)` into Alpine.js `x-data`
3. **Frontend JS** (create line 527-528, edit line 451-452): Each exam object in Alpine gets:
   ```js
   precio_sin_nota: parseFloat(ex.precio_sin_nota),
   precio_con_nota: parseFloat(ex.precio_con_nota)
   ```
4. **Price selection** (create line 645-647, edit line 635-637): `getPrecioExamen/examen` and `calcularSubtotalExamen` read `examenData.precio_sin_nota` or `examenData.precio_con_nota` based on the selected `tipoPrecio`
5. **Backend** (`RepaseService` lines 81-83, 185-187, 242-244): On save/update, re-resolves prices from the `Examen` model (not from form data), using the same `tipo_precio` switch
6. **Snapshot**: The resolved `precio_unitario_usado` is stored in `repase_examenes` — it's a historical record, not recalculated later

### All Files Referencing `precio_sin_nota` / `precio_con_nota`

| File | Role |
|------|------|
| `database/migrations/2026_03_04_130603_create_examenes_table.php` | Schema definition with CHECK constraint |
| `app/Models/Examen.php` | Model with fillable, casts (decimal:2), scopes (utilizationStats, profitabilityAnalysis, lowUtilization, withValidData, priceEfficiency) |
| `app/Http/Controllers/ExamenController.php` | Inline validation rules (required, numeric, min:0, max:999999.99, gt:precio_sin_nota) |
| `app/Services/RepaseService.php` | 3 places: create (lines 81-83), update (lines 185-187), calculateTotalExamenes (lines 242-244) |
| `database/factories/ExamenFactory.php` | Factory generates random prices (sin_nota 80-180, con_nota 15-40 higher) |
| `database/seeders/ExamenSeeder.php` | Seeds 7 predefined exams with hardcoded prices |
| `database/seeders/RepaseSeeder.php` | Lines 66-68: references `$examen->precio_sin_nota` / `precio_con_nota` with ±5% variation |
| `resources/views/examenes/index.blade.php` | Price display grid (lines 51, 54, 89, 99) |
| `resources/views/examenes/edit.blade.php` | Edit form with numeric inputs (lines 17-27) |
| `resources/views/repases/create.blade.php` | Alpine.js: serialized into JS (lines 527-528), displayed in form with `getPrecioExamen` (lines 632-634), calculated in `calcularSubtotalExamen` (lines 646-647) |
| `resources/views/repases/edit.blade.php` | Same pattern as create (lines 451-452, 622-624, 636-637) |
| `tests/Unit/Predictive/RepaseModelTest.php` | Creates Examen with prices 400.00 / 500.00 (lines 177-178) |
| `tests/Feature/PreventDuplicateSubmissionsTest.php` | Creates Examen with prices 100.00 / 150.00 (lines 35-36) |
| `tests/Feature/Predictive/ExportIntegrationTest.php` | Creates Examen with prices 100.00 / 120.00 (lines 192-193), reads `$examen->precio_sin_nota` (line 213) |
| `tests/Unit/Predictive/ModelExtensionsTest.php` | Creates exams with prices 100/150 and 200/250 (lines 177-184) |

### Existing Clinica-Examen Relationship

**None.** There is no pivot table, no foreign key, no many-to-many relationship. The `Clinica` model has:
- `HasMany` `repases()` → Repase
- `HasMany` `agendas()` → Agenda

The `Repase` model is the bridge: `Repase.clinica_id` → `Clinica`, and `RepaseExamen.examen_id` → `Examen`.

### Exam Edit View Structure

The edit view (`resources/views/examenes/edit.blade.php`) is a simple form:
- Single page with `$examen` passed via route model binding
- Two numeric inputs: `precio_sin_nota` and `precio_con_nota`
- No clinic selector, no per-clinic section
- Validation: both required, numeric, min:0, max:999999.99, `precio_con_nota > precio_sin_nota`

### RepaseService Price Resolution (Critical Path)

The `RepaseService` does **three** distinct price lookups:

1. **`createRepase()`** (line 81-83): `$precioUnitario = $data['tipo_precio'] === 'sin_nota' ? $examen->precio_sin_nota : $examen->precio_con_nota`
2. **`updateRepase()`** (line 185-187): Same logic
3. **`calculateTotalExamenes()`** (line 242-244): Same logic, called for the totals preview

All three follow the same pattern: load `$examen = Examen::findOrFail()`, then read the price directly from the model. **There is no clinic context in the lookup** — the service receives `$data['clinica_id']` for the repase but never passes it to the price resolution.

### Examen Scopes That Reference Prices Directly

Multiple scopes in `Examen.php` use `precio_sin_nota` and `precio_con_nota` directly in SQL:
- `scopeForUtilizationAnalysis` (lines 92-93, 99-100)
- `scopeUtilizationStats` (lines 119-120)
- `scopeProfitabilityAnalysis` (lines 235-236, 242)
- `scopeLowUtilization` (lines 265, 275)
- `scopeWithValidData` (lines 288-289)

These all reference `examenes.precio_sin_nota` and `examenes.precio_con_nota` directly in raw SQL — they do NOT go through accessors or model methods. Any price override scheme must account for this.

### Affected Areas

- `app/Models/Examen.php` — new relationship to clinicas, price resolver method
- `app/Models/Clinica.php` — new relationship to examenes
- `app/Services/RepaseService.php` — inject clinic-aware price resolution in 3 locations
- `app/Http/Controllers/ExamenController.php` — per-clinic price management endpoints
- `app/Http/Controllers/RepaseController.php` — pass clinic context when loading exams for the form
- `resources/views/examenes/index.blade.php` — add per-clinic pricing section
- `resources/views/examenes/edit.blade.php` — add per-clinic pricing panel
- `resources/views/repases/create.blade.php` — load per-clinic prices via Alpine when clinica changes
- `resources/views/repases/edit.blade.php` — same
- `database/migrations/` — new pivot table `clinica_examen` or `examen_clinica_precios`
- `database/factories/ExamenFactory.php` — update if factory generates per-clinic prices
- `database/seeders/ExamenSeeder.php` — unchanged (global defaults remain)
- `tests/` — all tests that reference prices need updates

### Approaches

1. **New pivot table `clinica_examen` with price columns** (Recommended)
   - Tables: `CREATE TABLE clinica_examen (clinica_id FK, examen_id FK, precio_sin_nota DECIMAL(10,2) NULL, precio_con_nota DECIMAL(10,2) NULL, PRIMARY KEY (clinica_id, examen_id))`
   - `Examen` model: `belongsToMany(Clinica::class)->withPivot('precio_sin_nota', 'precio_con_nota')`
   - `Clinica` model: `belongsToMany(Examen::class)->withPivot(...)`
   - Price resolver: helper method `Examen::getPriceForClinic(clinicaId, tipoPrecio)` that checks pivot, falls back to global
   - Pros: Clean relational model, queryable (can find which clinics override which prices), nullable columns = "use global", native Laravel pivot support, indexable
   - Cons: Migration, need to update all 3 price lookup points in RepaseService, frontend needs to pass clinic context when fetching prices
   - Effort: Medium

2. **JSON column `precios_por_clinica` on examenes**
   - Column: `precios_por_clinica JSON NULL` — like `{"1": {"sin_nota": 180, "con_nota": 200}, "5": {...}}`
   - Pros: No new table, easy to add, exam load fetches it automatically
   - Cons: Not queryable in SQL without JSON functions, violates normalization, harder to maintain with Laravel relationships, no FK constraints, no indexing, serialization complexity with the decimal cast
   - Effort: Low-Medium

3. **Separate `precios_examen` table with clinic_id (nullable)**
   - Table: `CREATE TABLE examen_precios (id PK, examen_id FK, clinica_id FK NULL, precio_sin_nota DECIMAL(10,2), precio_con_nota DECIMAL(10,2), UNIQUE(examen_id, clinica_id))`
   - Global defaults still live on `examenes` table
   - `clinica_id = NULL` → global price, `clinica_id = value` → per-clinic override
   - Pros: Flexible, can have multiple overrides, clean schema, FK constraints
   - Cons: More complex lookup logic (need to query override and fallback), similar effort to option 1
   - Effort: Medium

### Recommendation

**Approach 1 (new pivot table `clinica_examen`)** — it's the most idiomatic Laravel solution, keeps the schema clean, supports eloquent relationships natively, and nullable pivot columns cleanly express "use global fallback". The pivot approach also makes it easy to add future columns (e.g., `activo`, `descuento_maximo`) without schema changes.

The key implementation strategy:
1. Create migration for `clinica_examen` pivot with nullable price columns
2. Add `belongsToMany` on both models
3. Add a method `Examen::getPrecioParaClinica($clinicaId, $tipoPrecio)` that checks pivot first, falls back to `$this->{"precio_$tipoPrecio"}`
4. Add a method `Examen::getPreciosParaClinica($clinicaId)` returning `{sin_nota: X, con_nota: Y}` for the frontend
5. Update `RepaseService` to accept `$clinicaId` and use the resolver method
6. Update `ExamenController` to manage per-clinic prices
7. Update frontend: when clinica changes in repase form, reload per-clinic prices
8. Update scope SQL that references `examenes.precio_sin_nota` / `examenes.precio_con_nota` — these may need to remain as "the global price" reference (they're used for analytical reports, not transactional price lookups)

### Risks

- **Database CHECK constraint**: `chk_precio_sin_nota_menor` lives on `examenes` table but should NOT apply to per-clinic prices (a clinic could have different spreads). The pivot columns must NOT inherit this constraint.
- **Analytical scopes**: The scopes in `Examen.php` reference `examenes.precio_*` directly in SQL and are used for analysis reports. These represent the **global** price — the question is whether they should also reflect clinic overrides. If the analysis is clinic-scoped, it already queries via `repase_examenes.precio_unitario_usado` (the snapshot), so the global prices in scopes are only for baselines. Low risk.
- **Seeders/RepaseSeeder**: Uses `$examen->precio_sin_nota` directly with a ±5% variation. Since per-clinic prices are optional overrides, the seeder can remain using global prices or be updated to create per-clinic variations. Low risk.
- **Frontend complexity**: The Alpine.js form currently loads exam prices once at page load. To support per-clinic prices, it needs to fetch/set prices when the user changes the clinic dropdown. This is the riskiest part — the form is already complex with 700+ lines of Alpine.
- **Existing historical data**: All existing `repase_examenes` records already have `precio_unitario_usado` snapshotted — they will NOT be affected by price changes.
- **Test risk**: 4 test files create Examen instances with prices. These may need updates if the factory or model behavior changes.

### Ready for Proposal

**Yes** — the exploration is complete. The orchestrator should tell the user:
- Recommended approach: pivot table `clinica_examen`
- The main effort is backend (model, service, controller changes) + frontend (Alpine.js price loading when clinic changes)
- No existing data migration needed (historical repase_examenes already snapshotted)
- The global prices in `examenes` remain as the "default" fallback
- Analytical scopes continue to use the global prices as baselines
