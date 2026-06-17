# Tenant Data Isolation Specification

## Purpose

Enforce strict data isolation between empresas at the ORM, middleware, and raw SQL layers so no user from empresa A can access data belonging to empresa B.

## Requirements

### Requirement: Global Scope on Clinica, User, and Examen

`Clinica`, `User`, and `Examen` models MUST register a Laravel Global Scope that filters all queries by the current `empresa_id` retrieved from `EmpresaContext`. The scope SHALL add `WHERE empresa_id = ?` to every query. SaaS admin queries MUST explicitly call `withoutGlobalScope()` to bypass this filter.

#### Scenario: Scoped clinica query for regular user

- GIVEN user U belongs to empresa 1, and clinica C belongs to empresa 2
- WHEN `Clinica::all()` is called from a request authenticated as U
- THEN clinica C is NOT present in the result set

#### Scenario: SaaS admin bypasses global scope

- GIVEN a SaaS admin is authenticated on the `saas` guard
- WHEN `Clinica::withoutGlobalScope('empresa')->get()` is called
- THEN all clinicas across all empresas are returned

### Requirement: EmpresaContext Singleton

The system MUST provide an `EmpresaContext` singleton that holds the current `empresa_id`. This context SHALL be set by the `ScopeByEmpresa` middleware on every request that requires scoping. If no empresa context is set, Global Scopes MUST raise an exception to prevent accidental unscoped access.

#### Scenario: Context set from authenticated user

- GIVEN user U has `empresa_id = 1` and is authenticated
- WHEN the `ScopeByEmpresa` middleware processes a request
- THEN `EmpresaContext::currentId()` returns 1

#### Scenario: Missing context throws exception

- GIVEN no empresa context has been set
- WHEN a scoped model query executes without middleware
- THEN the system MUST throw `EmpresaContextNotSetException`

### Requirement: ScopeByEmpresa Middleware

A `ScopeByEmpresa` middleware MUST be applied to all `auth` and `subscription` route groups. It SHALL extract `empresa_id` from `auth()->user()` and bind it to `EmpresaContext`. SaaS admin routes under the `saas` guard SHALL NOT use this middleware.

#### Scenario: Middleware applied to protected route

- GIVEN `/clinicas` is behind the `empresa.scope` middleware group
- WHEN an authenticated user requests `/clinicas`
- THEN the middleware sets `EmpresaContext` before the controller executes

### Requirement: Raw SQL Scoping

All `DB::table()` and `DB::raw()` usage in `DashboardService`, `ReporteService`, and `BalanceService` MUST include explicit `WHERE empresa_id = ?` clauses using the current `EmpresaContext`. No raw SQL query that touches empresa-scoped entities SHALL execute without this filter.

#### Scenario: Dashboard query scoped by empresa

- GIVEN `DashboardService::getMonthlyRevenue()` uses `DB::table('repases')`
- WHEN called with empresa context set to 1
- THEN the raw SQL includes `WHERE clinicas.empresa_id = 1` via the clinica join chain

#### Scenario: Unscoped raw query is blocked

- GIVEN a raw SQL query in a service method touches `clinicas` without `empresa_id` filter
- WHEN audited against the scoping contract
- THEN it SHALL be flagged as a data-leak risk

### Requirement: FK Chain Scoping

Entities that inherit scoping via FK chains (`Repase` → `Clinica`, `Gasto` → `Repase` → `Clinica`, `Agenda` → `Clinica`) MUST resolve their empresa through the chain without requiring a direct `empresa_id` column. Scope enforcement SHALL occur at the point where the parent entity is loaded.

#### Scenario: Repase scoped through clinica chain

- GIVEN Repase R belongs to Clinica C which belongs to Empresa 1
- WHEN loaded from a request with `EmpresaContext = 1`
- THEN R is returned; from context 2 it is NOT returned
