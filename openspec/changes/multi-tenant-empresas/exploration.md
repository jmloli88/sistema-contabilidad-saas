# Exploration: Multi-Tenant Empresas

## Current State

### Current Architecture: Single-Tenant, Multi-Clinic

The system currently treats all data as **globally accessible within a single tenant**. There is no `empresa` (tenant) abstraction. What exists today:

- **Clinicas** are top-level entities. Every authenticated user sees ALL clinicas in every dropdown, dashboard chart, and report. There is zero data isolation.
- **Users** have an optional `clinica_id` (nullable FK added 2026-06-17). Users may be associated with a clinic, but this is purely organizational — it does NOT scope data access.
- **Examenes** are global. Prices are stored as global defaults on `examenes` with per-clinic overrides in the `clinica_examen` pivot table (per-clinic-exam-prices feature, already partially implemented).
- **Repases** belong to a clinica via `repases.clinica_id`.
- **Agendas** belong to a clinica via `agendas.clinica_id`.
- **Gastos** belong to a repase via `gastos.repase_id` (indirectly linked to clinica through the repase chain).
- **Subscriptions** are per-user (stripe-based via Laravel Cashier), with a "clinic-sharing" pattern where one user's active subscription covers all users in the same clinic.
- **SaaS Admin** (`SaasAdmin` model, `saas` guard) manages all users globally—can see, edit, extend/cancel subscriptions for any user across all clinics.

### Database Schema (Current)

```
saas_admins              ← SaaS platform admins (separate auth guard)
users                    ← Regular users (app auth guard)
├── role: administrador | usuario
├── clinica_id FK?       ← NULLABLE, organizational only, NOT an isolation boundary
└── subscriptions        ← Laravel Cashier (stripe_status, ends_at)

clinicas                 ← GLOBAL — no isolation
├── repases              ← clinica_id FK
│   ├── repase_examenes  ← repase_id FK, examen_id FK
│   └── gastos           ← repase_id FK
├── agendas              ← clinica_id FK
└── clinica_examen       ← pivot: clinica_id, examen_id (per-clinic prices)

examenes                 ← GLOBAL — no isolation
```

### Current Data Access Pattern

Every controller that loads clinicas, repases, examenes, or agendas does so **without any scoping**:

```php
// All controllers: no restriction on which user can see which data
$clinicas = Clinica::orderBy('nombre')->get();      // ALL clinicas
$repases  = Repase::with('clinica')->...->get();     // ALL repases
$examenes = Examen::orderBy('nombre')->get();        // ALL examenes
```

Filters by `clinica_id` exist as optional user-driven filters, not as mandatory data isolation.

## What Multi-Tenancy Means Here

Each "empresa" (SaaS customer) gets a fully isolated instance:

| Entity | Today (Global) | Target (Per-Empresa) |
|--------|---------------|---------------------|
| Clinicas | All users see all | Scoped to empresa |
| Examenes | Global catalog | Per-empresa catalog |
| Per-clinic exam prices | Via `clinica_examen` pivot | Pivot scoped to empresa's clinicas + examenes |
| Repases | Via clinica | Scoped through clinica → empresa |
| Agendas | Via clinica | Scoped through clinica → empresa |
| Gastos | Via repase | Scoped through repase → clinica → empresa |
| Users | Global list | Scoped to empresa |
| Subscriptions | Per-user, clinic-shared | Per-empresa |
| Prediction configs | Global configs | Per-empresa configs (maybe) |

### The "Empresa" Hierarchy

```
Empresa (new)
├── Users (administrador + usuarios of that empresa)
├── Clinicas (belong to empresa)
│   ├── Repases → RepaseExamenes → Examenes
│   ├── Agendas
│   └── Gastos (via Repase)
├── Examenes (catalog per empresa, includes default prices)
├── ClinicaExamen pivot (per-clinic override prices, scoped by empresa)
├── Subscription (shared across empresa's users)
└── Prediction Configurations (maybe scoped)
```

## Scope of Changes

### New Table Needed

**`empresas`**:
```sql
CREATE TABLE empresas (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre      VARCHAR(255) NOT NULL,
    slug        VARCHAR(255) UNIQUE,        -- For subdomain or URL prefix
    email       VARCHAR(255) NULL,          -- Contact email
    telefono    VARCHAR(20) NULL,
    direccion   TEXT NULL,
    is_active   BOOLEAN DEFAULT TRUE,
    settings    JSON NULL,                   -- Feature flags, config per empresa
    created_at  TIMESTAMP NULL,
    updated_at  TIMESTAMP NULL
);
```

### Existing Tables That Need `empresa_id`

| Table | Current FK Chain | New FK | Strategy |
|-------|-----------------|--------|----------|
| `clinicas` | No isolation | `empresa_id` NOT NULL | Direct column |
| `users` | `clinica_id` nullable | `empresa_id` NOT NULL | Direct column (make `clinica_id` nullable still OK) |
| `examenes` | Global | `empresa_id` NOT NULL | Direct column |
| `clinica_examen` | `clinica_id`, `examen_id` | Inherits scope via both FKs | No direct FK needed (scoped through clinica+examen) |
| `subscriptions` | `user_id` | `empresa_id` nullable (or link via user→empresa) | Direct column or through user |
| `prediction_configurations` | Global | `empresa_id` NULLABLE (0 = global) | Direct column |
| `prediction_configuration_audit` | Via user | Inherits scope | No direct FK needed |

### Tables That DO NOT Need `empresa_id` (inherit via FK chain)

| Table | Scope Path |
|-------|-----------|
| `repases` | → `clinica_id` → `clinica.empresa_id` |
| `repase_examenes` | → `repase.clinica.empresa_id` |
| `gastos` | → `repase.clinica.empresa_id` |
| `agendas` | → `clinica.empresa_id` |

These tables already have a path to the empresa through their existing FK chain. **No migration needed** — scoping is enforced via query constraints, not denormalized FKs.

### Tables That Stay Global

| Table | Reason |
|-------|--------|
| `saas_admins` | SaaS platform admins manage all empresas |
| `cache`, `jobs`, `sessions` | Laravel infrastructure |
| `password_reset_tokens` | Per-user, scoped via user |

### Subscription Model Decision

**Critical design fork**: Should subscriptions be per-user or per-empresa?

- **Per-user (current)**: Each user has their own Stripe subscription. The clinic-sharing hack (`hasActiveSubscriptionInClinic()`) checks if ANY user in the same clinic has an active subscription. This was built for the single-tenant model.
- **Per-empresa (target)**: The EMPRESA has one subscription. All users of that empresa share it. This is the standard SaaS model.

**Recommendation**: Per-empresa subscriptions. Add `empresa_id` to the `subscriptions` table. The subscription check becomes: "Does this empresa have an active subscription?" The Stripe customer would be the empresa, not individual users.

## Affected Models

All 10 models need changes:

| Model | Change | Risk |
|-------|--------|------|
| `Clinica` | +`empresa_id`, +`belongsTo(Empresa)`, scope `forCurrentEmpresa()` | High — used everywhere |
| `User` | +`empresa_id` (make required), update `hasActiveSubscriptionInClinic()` | High — auth core |
| `Examen` | +`empresa_id`, scope `forCurrentEmpresa()`, +`belongsTo(Empresa)` | High — pricing logic |
| `Repase` | No schema change, add `scopeForCurrentEmpresa()` via clinica | Medium — many scopes |
| `RepaseExamen` | No schema change, scope via repase | Low |
| `Gasto` | No schema change, scope via repase | Low |
| `Agenda` | No schema change, scope via clinica | Low |
| `SaasAdmin` | No change | None |
| `PredictionConfiguration` | +`empresa_id` nullable | Low |
| `PredictionConfigurationAudit` | No change (scoped via user→empresa) | Low |

**NEW MODEL**: `Empresa`:
```php
class Empresa extends Model {
    public function clinicas(): HasMany;
    public function users(): HasMany;
    public function examenes(): HasMany;
    public function subscriptions(): HasMany;  // or morphMany
    public function predictionConfigurations(): HasMany;
}
```

## All Affected Files (Complete Map)

### Controllers (16 files)

All controllers load clinicas globally and need empresa scoping:

| Controller | Current Pattern | Change |
|-----------|----------------|--------|
| `DashboardController` | `Clinica::orderBy('nombre')->get()` | Scope by empresa |
| `RepaseController` | `Clinica::orderBy('nombre')->get()` × 3 | Scope by empresa |
| `ClinicaController` | CRUD, no scoping | Scope CRUD by empresa |
| `ExamenController` | `Clinica::orderBy('nombre')->get()` | Scope by empresa |
| `AgendaController` | `Clinica::orderBy('nombre')->get()` | Scope by empresa |
| `CalendarioController` | `Clinica::orderBy('nombre')->get()` | Scope by empresa |
| `ReporteController` | `Clinica::orderBy('nombre')->get()` × 10+ | Scope by empresa |
| `PredictiveController` | Uses Repase, Clinica scopes | Add empresa scoping |
| `BalanceController` | `Clinica::orderBy('nombre')->get()` | Scope by empresa |
| `UserController` | `User::orderBy('name')->paginate()` | Scope by empresa |
| `SaaSAdminController` | Global access to ALL users/clinicas | Add empresa filter/management views |
| `BillingController` | Checks own subscription | Check empresa subscription |
| `StripeWebhookController` | (not read yet) | May need empresa context |
| `ProfileController` | Auth user only | May need empresa context |
| `Auth/RegisteredUserController` | Creates user | Assign user to empresa |
| `Auth/SaasLoginController` | SaaS admin auth | No change |

### Services (7 files)

| Service | Change |
|---------|--------|
| `DashboardService` | All queries need empresa scoping |
| `RepaseService` | Price resolution needs empresa context; createRepase scoped |
| `BalanceService` | All queries need empresa scoping |
| `Reportes/ReporteService` | All queries need empresa scoping |
| `Reportes/ExportService` | Pass empresa context |
| `Predictive/IncomePredictor` | Need empresa-scoped data |
| `Predictive/ExpenseForecaster` | Need empresa-scoped data |
| `Predictive/CapacityAnalyzer` | Need empresa-scoped data |

### Middleware (2 files + 1 new)

| Middleware | Change |
|-----------|--------|
| `EnsureSubscriptionIsActive` | Check empresa subscription instead of clinic-shared |
| `EnsureUserIsAdmin` | No change needed (role check stays) |

**NEW**: `ScopeByEmpresa` middleware — set the global empresa context in the request, used by global scopes.

### Routes

No route structure changes needed for existing app routes (they stay the same, just the data changes). SaaS admin routes may need new empresa management routes.

### Views (~30+ blade files)

Almost every view that lists or selects clinicas will need empres-scoped data. Key categories:

- **Clinica dropdowns**: `dashboard/index.blade.php`, `repases/create.blade.php`, `repases/edit.blade.php`, `repases/index.blade.php`, `reportes/*.blade.php`, `balances/*.blade.php`, `calendario/index.blade.php`, `agendas/index.blade.php`
- **User management**: `users/index.blade.php`, `users/create.blade.php`, `users/edit.blade.php`
- **Examen management**: `examenes/index.blade.php`, `examenes/edit.blade.php`
- **SaaS admin**: `saas-admin/dashboard.blade.php`, `saas-admin/index.blade.php`, `saas-admin/edit.blade.php` — these need empresa awareness

### Tests (46+ test files)

Every feature test that creates clinicas, users, repases, or examenes will need an empresa context. Nearly all existing factory calls need updating.

## Middleware Impact

### Current Middleware Stack

```
Route groups:
  web:  ['auth', 'verified', 'subscription']      → regular users
  admin: ['auth', 'verified', 'subscription', 'admin'] → administrador users
  saas: ['auth:saas']                               → saas admins
```

### Proposed Middleware Stack

```php
// NEW: Resolve current empresa from authenticated user
Route::middleware(['auth', 'verified', 'subscription', 'empresa.scope'])->group(function () {
    // All empresa-scoped routes
});

// 'empresa.scope' middleware does:
// 1. Get auth()->user()->empresa_id
// 2. Set it as a global context (e.g., via a service provider or context helper)
// 3. All global scopes use this context
```

The `empresa.scope` middleware would:
1. Extract `empresa_id` from the authenticated user
2. Set it on a `EmpresaContext` singleton or `request()->attributes`
3. Enable Laravel Global Scopes on `Clinica`, `User`, `Examen` that filter by the context's empresa_id

For SaaS admin routes: `saas` guard routes would NOT have empresa scoping — they see everything.

### Subscription Middleware Update

`EnsureSubscriptionIsActive` currently calls `$user->hasActiveSubscriptionInClinic()`. Under multi-tenancy, this becomes:

```php
$user->empresa->hasActiveSubscription();  // Check empresa-level subscription
```

## The "Per-Clinic Exam Prices" Feature Interaction

The `clinica_examen` pivot table already exists and is already live. This feature:

1. **Does NOT conflict** with multi-tenancy — it's an orthogonal feature
2. **DOES need empresa scoping**: When loading clinicas for the per-clinic prices UI, the clinica list must be scoped to the current empresa
3. **DOES need its migration updated**: The `clinica_examen` pivot references `clinicas` which will now have `empresa_id`. No schema change to the pivot itself needed — scoping is inherited through the `clinicas` FK.

The existing `Examen::getPrecioParaClinica()` method will continue to work correctly:
- The clinica lookup naturally scopes to the current empresa
- The global fallback price on `examenes` becomes the empresa's default price (since examenes will have `empresa_id`)

**Migration order matters**: The `clinica_examen` pivot migration already ran in production (2026_06_16_221351). We must NOT rerun it. It references `clinicas.id` and `examenes.id` via FKs — once those tables get `empresa_id`, the pivot is automatically scoped.

## SaaS Admin Impact

### Current SaaS Admin Capabilities

- View all users with subscription status (paginated list)
- Edit any user (name, email, role, clinica)
- Extend/cancel subscriptions
- View subscription history
- Dashboard with global KPIs (total users, active, expired, MRR)

### SaaS Admin Under Multi-Tenancy

The SaaS admin becomes an **empresa management panel**:

1. **Global KPIs become empresa-aware**:
   - Total empresas (instead of "clinicas activas")
   - Total users across all empresas
   - Active/expired subscriptions per empresa
   
2. **New capabilities needed**:
   - CRUD for empresas (create new SaaS customer)
   - View per-empresa details (users, clinicas, subscription)
   - Filter users by empresa
   - Manage empresa-level subscriptions (not per-user)

3. **Routes to add**:
   ```
   /saas/admin/empresas               → list all empresas
   /saas/admin/empresas/create        → new empresa form
   /saas/admin/empresas/{empresa}     → show empresa details (users, clinicas)
   /saas/admin/empresas/{empresa}/edit → edit empresa
   
   Existing user management stays, but empresas manage DROPS (users created via app, not SaaS admin)
   ```

4. **The SaaS admin panel no longer directly manages app users** — users belong to an empresa, and the empresa's own `administrador` manages their users via the app's `UserController` (which becomes empresa-scoped).

### SaaS Admin Data Model

Currently: `SaasAdmin` (separate table, separate guard) manages `User` records directly.

After: `SaasAdmin` manages `Empresa` records. The `User` records are managed by the empresa's own admin via empresa-scoped routes.

## Foreign Key Chain Map

```
Empresa
├── users           (empresa_id)
│   └── subscriptions (user_id → empresa_id via user)
├── clinicas        (empresa_id)
│   ├── repases     (clinica_id → empresa_id via clinica)
│   │   ├── repase_examenes (repase_id → clinica → empresa)
│   │   │   └── examenes (examen_id — must also be empresa-scoped)
│   │   └── gastos  (repase_id → clinica → empresa)
│   └── agendas     (clinica_id → empresa_id via clinica)
├── examenes        (empresa_id)
│   └── clinica_examen (clinica_id + examen_id — both empresa-scoped via their FKs)
└── prediction_configurations (empresa_id nullable)
```

## Approaches

### Option A: Incremental via Global Scopes (Recommended)

**Strategy**: Add `empresa_id` columns, Laravel Global Scopes, and middleware in phases without any big-bang rewrite.

**Phases**:
1. Create `empresas` table + `Empresa` model (no data migration — seed first empresa from existing state)
2. Add `empresa_id` to `clinicas`, `users`, `examenes` (nullable initially, populate migration assigns existing records to "seed" empresa)
3. Add Laravel Global Scopes to `Clinica`, `User`, `Examen` that filter by empresa context
4. Add `empresa.scope` middleware that sets empresa context
5. Make `empresa_id` NOT NULL after data migration
6. Update subscription model (per-empresa instead of per-user)
7. Update SaaS admin panel for empresa management
8. Update all controllers/services to use scoped queries

**Pros**:
- Each phase is independently deployable
- Global Scopes mean most existing queries automatically become scoped
- Can seed a "default" empresa for existing data
- No data loss risk
- Laravel Global Scope pattern is well-documented

**Cons**:
- Global Scopes can be tricky (need to use `withoutGlobalScope()` for admin queries)
- Multiple deployments needed
- Testing surface is large
- Effort: High (but spread across phases)

### Option B: Big-Bang Rewrite

**Strategy**: Build everything in a feature branch, migrate all data at once.

**Pros**:
- Single deploy
- Cleaner testing (one branch)
- Easier to reason about

**Cons**:
- Feature branch will diverge massively from main
- Review nightmare (thousands of changed files)
- Database migration for ALL existing data
- Blocked on everything being ready
- Effort: Very High (single massive deployment risk)

### Option C: Subdomain-Based Isolation (Future)

**Strategy**: Each empresa gets its own subdomain (empresa1.app.com, empresa2.app.com) with separate database or schema.

**Pros**:
- True physical isolation
- Cleaner separation
- Easier to reason about

**Cons**:
- Infrastructure complexity (separate DB per tenant?)
- Domain/DNS management
- Overkill for current scale
- Effort: Extreme

## Recommendation

**Option A (Incremental via Global Scopes)** is the only realistic approach for this codebase. Rationale:

1. **The current codebase already has 46+ test files, 16 controllers, 7 services, and ~30 views**. A big-bang rewrite would introduce regressions at scale.
2. **Global Scopes are the Laravel-native solution** for multi-tenancy. They require minimal code change per query.
3. **Incremental deployment** lets us ship fase 1 (empresas table + scoping) without changing all views at once.
4. **Existing data** can be migrated in a single `artisan migrate` step with a data migration that assigns all current records to a "seed" empresa.

### Implementation Strategy Details

**Phase 0**: Create `empresas` table and `Empresa` model. Seed one "seed" empresa. No functionality changes.

**Phase 1**: Add nullable `empresa_id` to `clinicas`, `users`, `examenes`. Data migration assigns ALL existing records to the seed empresa. Add Laravel Global Scopes (but they won't activate until middleware is added). Add `EmpresaContext` service provider.

**Phase 2**: Add `empresa.scope` middleware. All routes behind `auth` and `subscription` get empresa scoping automatically. SaaS admin routes stay unscoped via `withoutGlobalScope()`.

**Phase 3 (parallel)**: Update subscription model to be per-empresa. Migrate existing subscriptions.

**Phase 4**: Update SaaS admin panel for empresa management (CRUD + per-empresa views).

**Phase 5**: Remove nullable and make `empresa_id` NOT NULL (once all records have valid empresa_id).

## Risks

| Risk | Impact | Mitigation |
|------|--------|------------|
| **Data leak between empresas** | Critical — HIPAA/LGPD concern | Global Scopes + middleware + comprehensive testing. Every query MUST go through the scope. Test with `withoutGlobalScope()` to verify. |
| **SaaS admin accidentally scoped** | High — admin can't see all data | Use `withoutGlobalScope()` explicitly on all SaaS admin queries. Named scope methods (`->forCurrentEmpresa()`) safer than global scopes for admin paths. |
| **Per-clinic exam prices broken** | High — existing feature in use | The pivot table inherits scoping naturally. Must test that `getPrecioParaClinica()` still works when empresa context is set. |
| **Subscription check broken** | High — users lose access | The new `$user->empresa->hasActiveSubscription()` replaces the clinic-shared hack. Must migrate existing subscriptions cleanly. |
| **Existing reports/graphs break** | Medium — charts show wrong data | Every `DashboardService`, `ReporteService`, `BalanceService` method needs empresa-scoped queries. These are the riskiest changes because they use raw SQL/DB::table() which bypass Eloquent scopes. |
| **Predictive analytics scope loss** | Medium — predictive models trained on all data | If predictive models were trained on global data, per-empresa scoping will show them less data. Need to decide: keep global predictions OR per-empresa. |
| **Registration flow** | Medium — new users need empresa assignment | Registration must either: (a) only work via SaaS admin (invite-based), or (b) create a new empresa on signup. Current Breeze registration is open — needs change. |
| **Factory/Seeder breakage** | Medium — test factories create entities without empresa | All factories for `User`, `Clinica`, `Examen` need `empresa_id` attribute added. Older seeders need updating. |
| **Migration order with existing pivots** | Low — `clinica_examen` already has FK to clinicas/examenes | Add `empresa_id` to clinicas/examenes first, then the pivot inherits scoping. No schema change to pivot needed. |

## Migration Strategy

### Data Migration for Existing Records

```php
// In a migration after adding nullable empresa_id:
$seedEmpresa = Empresa::create(['nombre' => 'Zumed Medicina Diagnóstica', 'slug' => 'zumeddg']);

DB::table('clinicas')->update(['empresa_id' => $seedEmpresa->id]);
DB::table('users')->update(['empresa_id' => $seedEmpresa->id]);
DB::table('examenes')->update(['empresa_id' => $seedEmpresa->id]);
```

This means existing installations start with one empresa containing all their current data. New empresas are created via the SaaS admin panel.

### Rollback Plan

Each phase is independently rollbackable:
- Phase 0-2: Drop columns, remove middleware, remove scopes
- Phase 3: Revert subscription checking to clinic-shared pattern
- Phase 4: Revert SaaS admin routes
- Phase 5: Re-make columns nullable

## Ready for Proposal

**Yes** — the exploration is complete. Key findings:

1. The system is currently single-tenant with global data. All clinicas, examenes, users are visible to everyone.
2. The existing `clinica_id` on users is a recent addition (2026-06-17) and is organizational, NOT an isolation boundary.
3. **~30 files** need changes: 10 models (1 new), 16 controllers, 7 services, 2 middleware + 1 new, ~30+ views, 46+ tests.
4. The "per-clinic-exam-prices" feature is compatible — it inherits empresa scoping through existing FK chains.
5. **Recommendation**: Incremental approach (Option A) with Laravel Global Scopes + middleware. Phased over 5+ deployments.
6. **Critical risk**: Raw SQL queries in `DashboardService`, `ReporteService`, and `BalanceService` bypass Eloquent scopes and need manual empresa filters.
7. **Auth fork**: Must decide between per-user vs per-empresa subscriptions. Per-empresa is the standard SaaS model.

The proposal phase should address:
- Per-empresa vs per-user subscriptions decision
- Whether `PredictionConfiguration` needs empresa scoping
- Registration flow: invite-only (via SaaS admin) or self-service with empresa creation
- Whether to use subdomains now or add later
