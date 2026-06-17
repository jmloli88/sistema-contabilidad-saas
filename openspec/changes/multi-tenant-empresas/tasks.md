# Tasks: Multi-Tenant Empresas

## Review Workload Forecast

| Field | Value |
|-------|-------|
| Estimated changed lines | 2200–2600 |
| 400-line budget risk | High |
| Chained PRs recommended | Yes |
| Suggested split | 6 chained PRs (1 per phase) |
| Delivery strategy | ask-on-risk |
| Chain strategy | pending |

Decision needed before apply: Yes
Chained PRs recommended: Yes
Chain strategy: pending
400-line budget risk: High

### Suggested Work Units

| Unit | Goal | Likely PR | Base |
|------|------|-----------|------|
| 1 | Foundation: table + model | PR 1 | feature/multi-tenant-empresas |
| 2 | Schema: FK cols + data migration | PR 2 | PR 1 branch |
| 3 | Isolation: scopes + raw SQL fixes | PR 3 | PR 2 branch |
| 4 | Subscriptions: dual-path billing | PR 4 | PR 3 branch |
| 5 | SaaS admin: empresa CRUD | PR 5 | PR 4 branch |
| 6 | Hardening: NOT NULL + cleanup | PR 6 | PR 5 branch |

## Phase 0: Foundation

- [x] 0.1 [RED] Test: EmpresaFactory creates empresa
- [x] 0.2 [GREEN] Migration: `create_empresas_table`
- [x] 0.3 [GREEN] Model: `Empresa` with relationship stubs
- [x] 0.4 [GREEN] Factory: `EmpresaFactory`
- [x] 0.5 ✅ Gate: create & retrieve empresa via tinker

## Phase 1: Schema Expansion

- [x] 1.1 [RED] Test: factories assign empresa_id from seed empresa
- [x] 1.2 [RED] Test: migration adds FK columns correctly
- [x] 1.3 [GREEN] 4 migrations: add nullable `empresa_id` FK to clinicas, users, examenes, subscriptions
- [x] 1.4 [GREEN] Data migration: seed "zumeddg" empresa + assign all existing records
- [x] 1.5 [GREEN] Trait: `ScopedByEmpresa` (empresa BelongsTo + scopeForCurrentEmpresa + boot)
- [x] 1.6 [GREEN] Singleton: `EmpresaContext` (get/set/isSet)
- [x] 1.7 [GREEN] Update User, Clinica, Examen factories: +empresa relationship in definition
- [x] 1.8 [GREEN] Add `belongsTo(Empresa)` to Clinica, User, Examen models
- [x] 1.9 ✅ Gate: 318 passed, 19 pre-existing failures — no regressions

## Phase 2: Tenant Isolation

- [x] 2.1 [RED] Test: `ScopeByEmpresa` middleware sets context from auth user
- [x] 2.2 [RED] Test: GlobalScope filters clinicas by current empresa_id
- [x] 2.3 [RED] Test: cross-empresa isolation (User A cannot see User B's data)
- [x] 2.4 [RED] Test: `withoutGlobalScope()` returns all records (SaaS admin)
- [x] 2.5 [GREEN] Register `EmpresaContext` singleton in `AppServiceProvider` (no-op — EmpresaContext is a static class)
- [x] 2.6 [GREEN] Create `ScopeByEmpresa` middleware
- [x] 2.7 [GREEN] Activate GlobalScope in `ScopedByEmpresa::bootScopedByEmpresa()`
- [x] 2.8 [GREEN] Apply `empresa.scope` middleware to `auth` + `subscription` route groups
- [x] 2.9 [GREEN] DashboardService: 3 raw SQL locations → `+WHERE clinicas.empresa_id = ?`
- [x] 2.10 [GREEN] ReporteService: raw joins → `+WHERE clinicas.empresa_id` on all methods
- [x] 2.11 [GREEN] BalanceService: 2 raw SQL `gastos` + `repase_examenes` → +empresa_id filter
- [x] 2.12 [GREEN] Clinica scope methods (5 raw SQL scopes) → +empresa_id in joins
- [x] 2.13 [GREEN] Examen scope methods (4 raw SQL scopes) → +empresa_id in joins
- [x] 2.14 ✅ Gate: full suite passes (19 pre-existing, 0 new failures); cross-empresa isolation confirmed via TenantIsolationTest

## Phase 3: Subscriptions

- [x] 3.1 [RED] Test: `Empresa::hasActiveSubscription()` returns true/false
- [x] 3.2 [RED] Test: dual-path resolves empresa sub first, clinic-shared fallback second
- [x] 3.3 [GREEN] Add `hasActiveSubscription()` on `Empresa` model
- [x] 3.4 [GREEN] Update `EnsureSubscriptionIsActive` with dual-path logic + logging
- [x] 3.5 [GREEN] SaaS admin: empresa subscription management (Stripe actions)
- [x] 3.6 ✅ Gate: dual-path subscribed/expired scenarios pass; log records correct path

## Phase 4: SaaS Admin

- [ ] 4.1 [RED] Test: SaaS admin lists all empresas at `/saas/admin/empresas`
- [ ] 4.2 [RED] Test: create empresa validates unique slug
- [ ] 4.3 [RED] Test: detail dashboard shows users, clinicas, sub status
- [ ] 4.4 [RED] Test: user list filterable by empresa dropdown
- [ ] 4.5 [GREEN] `EmpresaController`: full CRUD with `withoutGlobalScope()`
- [ ] 4.6 [GREEN] Routes: add empresa CRUD under `saas` guard
- [ ] 4.7 [GREEN] Blade views: `saas-admin/empresas/` (index, create, show, edit, form partial)
- [ ] 4.8 [GREEN] SaaSAdminController dashboard: +empresa-count KPI cards
- [ ] 4.9 [GREEN] SaaS admin user list: +empresa filter dropdown (default: All)
- [ ] 4.10 ✅ Gate: full suite passes; manual CRUD flow verified in browser

## Phase 5: Hardening

- [ ] 5.1 [RED] Test: inserting clinica/user/examen without empresa_id fails
- [ ] 5.2 [GREEN] 3 migrations: make `empresa_id` NOT NULL on clinicas, users, examenes
- [ ] 5.3 [GREEN] Remove clinic-shared fallback from `EnsureSubscriptionIsActive`
- [ ] 5.4 [GREEN] Remove legacy `hasActiveSubscriptionInClinic()` method
- [ ] 5.5 [REFACTOR] Drop `subscriptions.user_id` nullable fallback logic
- [ ] 5.6 ✅ Gate: full suite passes; NOT NULL constraint enforced at DB level
