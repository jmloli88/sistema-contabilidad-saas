# Design: Dynamic Exam Management

## Technical Approach

Add `is_active` (TINYINT(1) DEFAULT 1) to `examenes` — NOT SoftDeletes — to avoid global-scope interference with existing analytical scopes (`scopeUtilizationStats`, `scopeProfitabilityAnalysis`, etc.) that JOIN through `repase_examenes` across ALL exams for historical reporting. A named `scopeActive()` stays opt-in where filtering matters (repase forms, exam index).

New CRUD routes under the existing admin middleware group. Per-empresa auto-seeding via `Empresa::created` event listener registered in `AppServiceProvider::boot()`, keeping the bootstrap file clean.

## Architecture Decisions

### Decision 1: `is_active` Boolean Column

| Option | Tradeoffs | Decision |
|--------|-----------|----------|
| `is_active` TINYINT(1) DEFAULT 1 | Explicit column; must add `->active()` at each query site | ✅ Chosen |
| Laravel SoftDeletes | Built-in scopes; `withTrashed()` available | ❌ Breaks 12+ analytical scopes that LEFT JOIN through `repase_examenes` — SoftDeletes would silently exclude inactive exams from historical reports |

**Rationale**: The `Examen` model has extensive analytical scopes (`scopeUtilizationStats`, `scopeProfitabilityAnalysis`, `scopeLowUtilization`) that JOIN `repase_examenes` with `LEFT JOIN`. SoftDeletes' global scope would hide inactive exams from ALL queries, corrupting historical trend data. An explicit `scopeActive()` is opt-in — we add it ONLY to the two RepaseController query sites and the exam index listing.

### Decision 2: Event Listener Location

| Option | Tradeoffs | Decision |
|--------|-----------|----------|
| `AppServiceProvider::boot()` | Single file; matches existing Cashier setup pattern | ✅ Chosen |
| Laravel 12 `->withEvents()` in `bootstrap/app.php` | Clean separation; app.php already has middleware config | ❌ Adds new infrastructure for a single listener |
| Dedicated Listener class + Observer | "Laravel way"; testable in isolation | ❌ Over-engineering for 5 lines of seeding logic |

**Rationale**: The project already registers Cashier in `AppServiceProvider::boot()`. A single `Empresa::created(fn) ` listener there follows the existing convention without adding new files or `bootstrap/app.php` complexity.

### Decision 3: Toggle as Separate Route

**Choice**: `PATCH /examenes/{examen}/toggle` → dedicated `toggleActive()` method.

**Alternatives**: (a) fold into `update()` with checkbox, (b) inline Alpine.js AJAX toggle.

**Rationale**: Separate route keeps the toggle a single-click action (no form submission). Matches RESTful pattern for partial state mutation. The `update()` method already handles complex per-clinic price logic — mixing toggle state there would couple concerns unnecessarily.

## Data Flow

```
┌──────────────┐     POST /examenes      ┌────────────────────┐
│  Admin View  │ ───────────────────────→ │ ExamenController   │
│  (create/    │      validates nombre,   │ @store()           │
│   edit forms)│      precios             │ creates with       │
└──────┬───────┘                          │ empresa_id from    │
       │                                  │ EmpresaContext     │
       │  PATCH /examenes/{id}/toggle     └────────────────────┘
       ├──────────────────────────────────→ @toggleActive()
       │                                   flips is_active
       │
       │  DELETE /examenes/{id}
       ├──────────────────────────────────→ @destroy()
       │                                   checks repaseExamenes
       │                                   → exists() ? 403 : delete
       │
       ▼
┌──────────────┐     RepaseController     ┌────────────────────┐
│ Repase Forms │ ←── @create()/@edit() ── │ Examen::active()   │
│ (Alpine.js)  │     $examenes (only      │ → where is_active=1│
│              │     active exams)        │ → get()            │
└──────────────┘                          └────────────────────┘

   Empresa::create()
        │
        ▼
   AppServiceProvider::boot()
   → Empresa::created event
        │
        ├─ empresa->examenes()->count() === 0 ?
        │  YES → Examen::insert(7 defaults with empresa_id)
        │  NO  → skip (idempotent)
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `database/migrations/..._add_is_active_to_examenes.php` | Create | `$table->boolean('is_active')->default(true)` |
| `app/Models/Examen.php` | Modify | `is_active` fillable + boolean cast; `scopeActive()`; static `defaults(): array` |
| `app/Http/Controllers/ExamenController.php` | Modify | `create()`, `store(Request)`, `destroy(Examen)`, `toggleActive(Examen)` |
| `routes/web.php` | Modify | 4 routes: `create`, `store`, `destroy`, `toggle` in admin group |
| `resources/views/examenes/create.blade.php` | Create | Form: nombre, precio_sin_nota, precio_con_nota |
| `resources/views/examenes/index.blade.php` | Modify | "Nuevo Examen" button; Activate/Deactivate toggle per row; Delete button (hidden if has history); Active/Inactive badge |
| `resources/views/examenes/edit.blade.php` | Modify | Add `nombre` field (editable) |
| `app/Http/Controllers/RepaseController.php` | Modify | `->active()` on both `Examen::` queries in `create()` and `edit()` |
| `app/Providers/AppServiceProvider.php` | Modify | `Empresa::created(fn)` → seed 7 `Examen::defaults()` if count == 0 |
| `database/seeders/ExamenSeeder.php` | Modify | Use `Examen::defaults()`; remove hardcoded "Default Seed Empresa" |
| `database/factories/ExamenFactory.php` | Modify | Add `'is_active' => true` |

## Key Signatures

```php
// Examen model additions
public function scopeActive(Builder $query): Builder       // where('is_active', true)
public static function defaults(): array                    // static 7-exam array

// ExamenController new methods
public function create(): View
public function store(Request $request): RedirectResponse
public function destroy(Examen $examen): RedirectResponse   // blocks if repaseExamenes->exists()
public function toggleActive(Examen $examen): RedirectResponse

// RepaseController query change (in create & edit)
Examen::active()->select(...)->orderBy('nombre')->get()
Examen::active()->with('clinicas')->get()  // for $preciosPorClinica
```

## UI Wireframes

**Exam Index (table row, new columns)**:
```
[Nombre]            [P.Sin Nota] [P.Con Nota] [Estado]       [Acciones]
EEG c/mapeamento    R$200,00     R$220,00     🟢 Active      [✏️ Edit] [⏸️ Deactivate] [🗑️]
EEG                 R$100,00     R$120,00     🔴 Inactive    [✏️ Edit] [▶️ Activate]   [🗑️]
                                                     ↑ hidden if has repase history
[+ Nuevo Examen]  ← top-right button
```

**Exam Create Form (new modal/page)**:
```
Nombre: [______________________________]
Precio Sin Nota: [____.00]  Precio Con Nota: [____.00]
[Cancelar]  [Guardar Examen]
```

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | `scopeActive()` returns only active exams | PHPUnit, in-memory SQLite |
| Feature | Admin can create exam; appears in index | `actingAs(admin)->post(route('examenes.store'), [...])` |
| Feature | Deactivate hides from repase create form | Create exam, toggle off, assert not in repase/create response |
| Feature | Delete blocked when exam has repase history | Create repase with exam, assert delete returns error |
| Feature | New empresa auto-seeds 7 defaults | Create empresa, assert examenes count == 7 |
| Regression | Existing analytical scopes still work | Run existing `ExamenPrecioClinicaTest` suite |

## Migration / Rollout

- Migration is additive only — zero-downtime deploy. Existing exams get `is_active = true` via column default.
- Rollback: `php artisan migrate:rollback --step=1` removes column. Revert routes + controller methods. Inactive exams reappear.

## Open Questions

- [ ] Should `nombre` be unique per empresa? Currently only indexed, not constrained. Validate at controller level with `unique:examenes,nombre,NULL,id,empresa_id,{context}`?
- [ ] Should the toggle and delete routes live under the same admin middleware or be more restrictive (e.g., `admin` only)? Currently exam routes are already admin-gated.
