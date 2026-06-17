# Design: Multi-Tenant Empresas

## Technical Approach

Laravel Global Scopes + `EmpresaContext` singleton + `ScopeByEmpresa` middleware. All tenant-scoped Eloquent queries auto-filter by `empresa_id`. Raw SQL paths get manual `WHERE` clauses. SaaS admin routes bypass scopes via `withoutGlobalScope()`. 5-phase incremental rollout, each phase independently deployable and rollbackable.

## Architecture Decisions

| Decision | Choice | Rejected | Rationale |
|----------|--------|----------|-----------|
| Tenancy isolation | Global Scopes (Option A) | DB-per-tenant, subdomain routing | Scales with current data; no infra changes; native Laravel pattern with middleware toggle |
| Context carrier | `EmpresaContext` singleton (app container) | `request()->attributes`, session | Survives queue jobs/console commands; explicit lifecycle; testable in isolation |
| Subscription model | Per-empresa (add `subscriptions.empresa_id`) | Per-user with clinic-sharing hack | Standard SaaS model; one Stripe customer = one empresa; simpler billing |
| Repase/Gasto/Agenda scoping | FK chain via Clinica (no direct `empresa_id`) | Denormalized `empresa_id` on every table | Single source of truth; no cascading migration; existing FK path already exists |
| SaaS admin scope | `withoutGlobalScope()` on all admin queries | Separate DB connection | Minimal code change; same models; static analysis can enforce |

## Key Signatures

```php
// app/Context/EmpresaContext.php — singleton bound in AppServiceProvider
class EmpresaContext {
    private ?int $empresaId = null;
    public function set(?int $id): void;
    public function get(): ?int;     // returns null when unset (saas guard)
    public function isSet(): bool;
}

// app/Models/Concerns/ScopedByEmpresa.php — trait (NOT a booted global scope)
trait ScopedByEmpresa {
    public function empresa(): BelongsTo;
    public function scopeForCurrentEmpresa(Builder $q): Builder;  // ->where('empresa_id', EmpresaContext::get())
    protected static function bootScopedByEmpresa(): void;         // registers global scope via static::addGlobalScope()
    public static function bootWithoutEmpresaScope(): void;        // modifier for SaaS admin
}

// app/Models/Empresa.php — new tenant model
class Empresa extends Model {
    public function users(): HasMany;
    public function clinicas(): HasMany;
    public function examenes(): HasMany;
    public function subscriptions(): HasMany;
    public function hasActiveSubscription(): bool;
}

// app/Http/Middleware/ScopeByEmpresa.php — sets context from auth user
class ScopeByEmpresa {
    public function handle(Request $request, Closure $next): Response {
        EmpresaContext::set(auth()->user()?->empresa_id);
        return $next($request);
    }
}
```

## Middleware Flow

```
Request → auth → verified → subscription → empresa.scope → controller
                                                │
                                    EmpresaContext::set(user.empresa_id)
                                                │
                                    Clinica::booted() → addGlobalScope(empresa_id = context)
                                    User::booted()   → addGlobalScope(empresa_id = context)
                                    Examen::booted() → addGlobalScope(empresa_id = context)
```

SaaS admin routes (`auth:saas`) do NOT include `empresa.scope` — `EmpresaContext::get()` returns `null`, global scopes become no-ops.

## Data Model

```
empresas (new) ──────────┐
│ id, nombre, slug,      │
│ email, is_active,      │
│ settings (JSON)        │
├── clinicas ────────────┤ FK empresas.id
│   ├── repases          │ (via clinicas.id)
│   │   ├── repase_examenes (via repases.id)
│   │   └── gastos       │ (via repases.id)
│   └── agendas          │ (via clinicas.id)
├── users ───────────────┤ FK empresas.id
│   └── subscriptions ───┤ FK empresas.id (new nullable) + FK user_id
├── examenes ────────────┤ FK empresas.id
└── prediction_configs ──┤ FK empresas.id (nullable, NULL = global)
```

Tables inheriting scope via FK chain (NO migration): `repases`, `repase_examenes`, `gastos`, `agendas`, `clinica_examen` (pivot).

## Raw SQL Strategy

Every `DB::table()` / `DB::raw()` / `selectRaw` that joins or queries scoped entities needs manual `empresa_id` filter WHERE the query does NOT go through Eloquent Global Scopes:

| Service/Model | Raw SQL Locations | Change |
|---------------|-------------------|--------|
| `DashboardService` | `getGastosPorCategoriaChart` (L255: `DB::table('gastos')`), `getTopExamenesChart` (L319: `DB::table('repase_examenes')`), `getDiasCobroPorClinicaChart` (L433: `DB::table('repases')`) | Add `.join('clinicas', ...)->where('clinicas.empresa_id', EmpresaContext::get())` or pass `empresa_id` via filter param |
| `ReporteService` | All methods: `Clinica::query()->leftJoin('repases'...)` and `Examen::query()->leftJoin(...)` — these hit Eloquent but scopes on Clinica are bypassed by raw `leftJoin` | Add `->where('clinicas.empresa_id', EmpresaContext::get())` on every Clinica/Examen base query that uses raw joins |
| `BalanceService` | `getGastosPorCategoria` (L104: `DB::table('gastos')`), `getTopExamenes` (L125: `DB::table('repase_examenes')`), `getBalancesPorPeriodo`/`getResumenEjecutivo` (Eloquent Repase, safe via scope chain) | Same pattern as Dashboard — join clinicas and filter by `empresa_id` |
| `Clinica` scopes | `scopePerformanceComparison`, `scopeMonthlyUtilization`, `scopeGrowthAnalysis`, `scopeCapacitySaturation`, `scopeWithCapacityAlerts` — use `leftJoin('repases'...)` bypassing global scope on Clinica itself | Wrapper around base query: `Clinica::withoutGlobalScope('ScopedByEmpresa')->forCurrentEmpresa()->...` or add `where clinicas.empresa_id` explicitly |
| `Examen` scopes | `scopeUtilizationStats`, `scopeProfitabilityAnalysis`, etc. — raw joins bypass Examen scopes | Add `->where('examenes.empresa_id', EmpresaContext::get())` in each raws-scope |
| `RepaseService` | `createRepase`/`updateRepase`: validates `clinica_id` from request — must verify clinica belongs to current empresa | Add `Clinica::forCurrentEmpresa()->findOrFail($data['clinica_id'])` before creating repase |
| Predictive services | `IncomePredictor`, `ExpenseForecaster`, `CapacityAnalyzer`: use `Repase::forPrediction()` which respects FK chain | Pass `empresa_id` as filter to scope clinicas; models already filter by `clinica_id` so chain is safe |

**Pattern**: Where a query hits a scoped table directly via `DB::` instead of Eloquent, inject `.where('{table}.empresa_id', EmpresaContext::get())`.

## Subscription Architecture

**Dual-path transition** in `EnsureSubscriptionIsActive`:

```
1. Try EmpresaContext::get() → Empresa::find() → hasActiveSubscription()
2. If empresa has no active sub, fall back to User::hasActiveSubscriptionInClinic()
3. Log which path resolved (monitoring)
4. Remove fallback in Phase 5 after all data migrated
```

`subscriptions` table gets nullable `empresa_id`. SaaS admin creates new subscriptions at empresa level. `Empresa::hasActiveSubscription()` checks `subscriptions WHERE empresa_id = ? AND stripe_status = 'active' AND ends_at > now()`.

## SaaS Admin Design

New routes in `routes/web.php` under existing `auth:saas` group:

```
GET    /saas/admin/empresas              → empresa list
GET    /saas/admin/empresas/create       → create form
POST   /saas/admin/empresas              → store
GET    /saas/admin/empresas/{empresa}    → show (users, clinicas, sub status)
GET    /saas/admin/empresas/{empresa}/edit → edit form
PUT    /saas/admin/empresas/{empresa}    → update
DELETE /saas/admin/empresas/{empresa}    → delete (soft, is_active=false)
```

Controller: `app/Http/Controllers/EmpresaController.php` (new). Views: `resources/views/saas-admin/empresas/` (5 blade files). Existing `SaaSAdminController` dashboard gains empresa-count KPIs. User list gets `empresa` filter dropdown. Subscription CRUD moves from per-user to per-empresa.

## Migration Strategy

| Phase | Migration Files | What |
|-------|----------------|------|
| **P0** | `{ts}_create_empresas_table.php` | Create `empresas` table. Seed one empresa. No FK changes. |
| **P1** | `{ts}_add_empresa_id_to_clinicas.php`<br>`{ts}_add_empresa_id_to_users.php`<br>`{ts}_add_empresa_id_to_examenes.php`<br>`{ts}_add_empresa_id_to_subscriptions.php` | Add nullable `empresa_id` columns. Data migration: assign all existing rows to seed empresa. Define Global Scopes and `EmpresaContext` (not activated yet). Update factories. |
| **P2** | `{ts}_add_empresa_id_to_prediction_configurations.php` | Add nullable `empresa_id`. Activate `empresa.scope` middleware on all `auth`+`subscription` routes. SaaS admin uses `withoutGlobalScope()`. |
| **P3** | (none — app logic only) | Dual-path subscription check. SaaS admin manages empresa subscriptions. |
| **P4** | (none — app logic + views) | Empresa CRUD routes, controller, views. SaaS admin dashboard KPIs. |
| **P5** | `{ts}_make_empresa_id_not_null_on_clinicas.php`<br>`{ts}_make_empresa_id_not_null_on_users.php`<br>`{ts}_make_empresa_id_not_null_on_examenes.php` | Make `empresa_id` NOT NULL. Remove dual-path fallback. Clean up `hasActiveSubscriptionInClinic()`. |

Each phase has its own rollback migration. Phase order enforces: FK columns exist before scopes activate; scopes activate before NOT NULL constraints.

## Testing Strategy

| Layer | Coverage | Approach |
|-------|----------|----------|
| Unit | `EmpresaContext`, `ScopeByEmpresa` middleware, Global Scope trait | Mock `EmpresaContext::get()`, assert query WHERE clauses |
| Integration | Cross-empresa isolation per entity | `actingAs(userA)` creates clinica → `actingAs(userB)` asserts not visible. Test `withoutGlobalScope()` for SaaS admin |
| Factories | All 46 test files | `UserFactory`, `ClinicaFactory`, `ExamenFactory` get +`empresa_id`. `TestCase` base class sets up seed empresa + context before each test |

No E2E (per project config).

## Open Questions

- [ ] Pricing: per-empresa subscription tiers or flat R$50/active? (Product decision — use flat R$50 until defined)
- [ ] Registration flow: self-service creates new empresa, or invite-only via SaaS admin? (Out of scope for MVP — existing registration disabled, SaaS admin creates)
- [ ] Examen catalog: should empresas share a global catalog (empresa_id=NULL=global) with per-empresa overrides? Or fully scoped? (Design assumes fully scoped per exploration recommendation)
