# Proposal: Multi-Tenant Empresas

## Intent
Transform the single-tenant system into a multi-tenant SaaS where each "empresa" gets complete data isolation: separate clinicas, examenes, users, prices, agendas, repases, and subscriptions.

## Scope

### In Scope
- Empresa model, migration, CRUD via SaaS admin panel
- Data isolation via Laravel Global Scopes + `ScopeByEmpresa` middleware + `EmpresaContext`
- Per-empresa subscriptions (replaces per-user Cashier model + clinic-sharing hack)
- Scoped entities: clinicas, users, examenes, repases, agendas, gastos, prediction configs
- SaaS admin: empresa list, create, edit, detail (users/clinicas/subscription per empresa)
- Seed empresa migration for all existing production data

### Out of Scope
- Subdomain-based or per-empresa database isolation (future Option C)
- Self-service empresa registration (MVP is invite-only via SaaS admin)
- Per-empresa predictive model training (keep global for now)
- Migrating existing Stripe subscriptions (dual-path during transition)

## Capabilities

### New Capabilities
- `empresa-tenant`: Empresa model, table, relationships (hasMany: clinicas, users, examenes, subscriptions, predictionConfigs). Seed migration creates one empresa from all existing data.
- `tenant-data-isolation`: Global Scopes on Clinica, User, Examen enforcing `empresa_id` filter. `EmpresaContext` singleton set by `ScopeByEmpresa` middleware at route level. Raw SQL paths (DashboardService, ReporteService, BalanceService) get manual `WHERE empresa_id` clauses. SaaS admin uses `withoutGlobalScope()`.
- `empresa-subscription`: Per-empresa billing via `subscriptions.empresa_id`. Subscription check becomes `$user->empresa->hasActiveSubscription()`. SaaS admin manages Stripe at empresa level. Dual-path during transition: empresa check first, clinic-shared fallback.
- `saas-empresa-admin`: Empresa CRUD routes under `saas` guard. Per-empresa dashboard: active users, clinicas, subscription status, MRR. User list filterable by empresa.

### Modified Capabilities
None — `openspec/specs/` is empty; this is a greenfield multi-tenancy layer on an existing codebase.

## Approach
**Incremental via Global Scopes** (Option A from exploration). 5 deployable phases:

1. **Phase 0**: `empresas` table + `Empresa` model. No functionality changes.
2. **Phase 1**: Nullable `empresa_id` on clinicas, users, examenes. Data migration assigns all records to seed empresa. Global Scopes defined but not yet activated.
3. **Phase 2**: Activate Global Scopes + `ScopeByEmpresa` middleware on all `auth` + `subscription` routes. SaaS admin routes stay unscoped via `withoutGlobalScope()`.
4. **Phase 3**: Per-empresa subscriptions. Migrate check from clinic-shared to empresa-level. SaaS admin manages empresa subscriptions.
5. **Phase 4**: SaaS admin empresa CRUD + per-empresa detail views.
6. **Phase 5**: Make `empresa_id` NOT NULL on clinicas, users, examenes.

## Affected Areas

| Area | Impact | Description |
|------|--------|-------------|
| `app/Models/Empresa.php` | New | Core tenant model — hasMany clinicas, users, examenes, subscriptions |
| `app/Models/{Clinica,User,Examen}.php` | Modified | +empresa_id, +belongsTo(Empresa), +GlobalScope |
| `app/Models/{Repase,Gasto,Agenda}.php` | Modified | scopeForCurrentEmpresa() via FK chain |
| `app/Http/Middleware/ScopeByEmpresa.php` | New | Sets EmpresaContext from `auth()->user()->empresa_id` |
| `app/Services/*` (7 files) | Modified | DashboardService, ReporteService, BalanceService: manual WHERE on raw SQL |
| `app/Http/Controllers/*` (16 files) | Modified | All queries go through scoped Eloquent or service methods |
| `app/Http/Middleware/EnsureSubscriptionIsActive.php` | Modified | empresa subscription check replaces clinic-shared |
| `database/migrations/` | Modified | New empresas table; add empresa_id FK columns; data migration |
| `database/factories/` | Modified | User, Clinica, Examen factories +empresa relationship |
| `resources/views/` (30+ files) | Modified | Clinica/examen/user dropdowns now scoped; new SaaS admin empresa views |
| `routes/saas.php` | Modified | Empresa CRUD routes under `saas` guard |
| `tests/` (46+ files) | Modified | All factories get empresa context; actingAs with empresa-scoped user |

## Risks

| Risk | Likelihood | Mitigation |
|------|------------|------------|
| Raw SQL bypasses scopes (Dashboard/Reporte/Balance) | High | Manual `WHERE empresa_id = ?` in every `DB::table()`/`DB::raw()` call. Audit all 7 services before Phase 2 deploy. |
| 46+ test files break on factory changes | High | Update factories in Phase 1. All feature tests get `actingAs(user)` with empresa context. Run full suite per phase. |
| Subscription check broken during transition | High | Dual-path: try empresa subscription first, clinic-shared fallback. Log which path is used. Remove fallback in Phase 5. |
| Data leak between empresas | Medium | Global Scopes enforced at ORM level. Integration tests verify cross-empresa isolation per entity type. |
| SaaS admin accidentally scoped | Medium | `withoutGlobalScope()` on all admin queries by convention. Static analysis rule to flag scoped queries in admin controllers. |
| Existing Stripe subscriptions incompatible | Medium | Keep per-user subscriptions during transition. SaaS admin maps user→empresa for billing view. Full migration in Phase 5. |

## Rollback Plan
Each phase independently rollbackable:
- **Phase 0-2**: Drop empresa_id columns, remove Global Scopes + middleware, revert to unscoped queries
- **Phase 3**: Restore `hasActiveSubscriptionInClinic()`, drop `subscriptions.empresa_id`
- **Phase 4**: Remove SaaS admin empresa routes/views/controllers
- **Phase 5**: Make empresa_id columns nullable again (reverse migration)

## Dependencies
- Laravel Cashier (already installed, Stripe integration)
- Existing `clinica_examen` pivot migration (in production — no changes; inherits scoping via FKs)
- `users.clinica_id` nullable FK (added 2026-06-17 — remains nullable, organizational only)

## Success Criteria
- [ ] User from empresa A cannot see clinicas, examenes, repases, or users of empresa B
- [ ] SaaS admin can CRUD empresas and view per-empresa subscription status + user/clinica counts
- [ ] All existing production data migrated to seed empresa with zero loss
- [ ] All 46 test suites pass with empresa-context factories
- [ ] Subscription gating checks empresa subscription, never clinic-shared post-Phase-5
