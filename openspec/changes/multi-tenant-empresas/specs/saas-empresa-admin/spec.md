# SaaS Empresa Admin Specification

## Purpose

Provide SaaS platform admins with CRUD management of empresas and per-empresa visibility into users, clinicas, subscription status, and billing.

## Requirements

### Requirement: Empresa CRUD

The system MUST expose CRUD routes for empresas under the `saas` guard at `/saas/admin/empresas`. All queries SHALL use `withoutGlobalScope()` to ensure SaaS admins see data across all empresas. Creation MUST validate unique `slug`. Deletion SHOULD be soft (set `is_active = false`) rather than hard-delete.

#### Scenario: List all empresas

- GIVEN 3 empresas exist in the database
- WHEN a SaaS admin visits `/saas/admin/empresas`
- THEN all 3 empresas are displayed with nombre, slug, is_active, and created_at

#### Scenario: Create empresa

- GIVEN a SaaS admin fills the create form with nombre, slug, and optional contact fields
- WHEN the form is submitted
- THEN a new empresa is persisted with `is_active = true`
- AND the admin is redirected to the empresa detail page

#### Scenario: Edit empresa

- GIVEN empresa E exists
- WHEN a SaaS admin updates its `nombre` and `is_active`
- THEN the changes are persisted
- AND the empresa list reflects the updated name

### Requirement: Per-Empresa Detail Dashboard

The `/saas/admin/empresas/{empresa}` view MUST display: total active users, total clinicas, current subscription status (active/expired/none), MRR (monthly recurring revenue), and a paginated list of users belonging to that empresa. Clinica and user counts SHALL be live queries, not cached counts.

#### Scenario: View empresa with active subscription

- GIVEN empresa E has 5 users, 2 clinicas, and an active Stripe subscription at $99/month
- WHEN a SaaS admin views E's detail page
- THEN the page shows "5 users", "2 clinicas", "Active ($99/mo)"

#### Scenario: View empresa with no subscription

- GIVEN empresa E has 3 users and no subscription record
- WHEN a SaaS admin views E's detail page
- THEN subscription status displays "None" with a prompt to create one

### Requirement: User List Filtered by Empresa

The existing SaaS admin user list SHALL add an optional empresa filter dropdown. When an empresa is selected, only users with matching `empresa_id` are shown. The filter MUST default to "All Empresas".

#### Scenario: Filter users by empresa

- GIVEN 10 users exist across 2 empresas (6 in empresa A, 4 in empresa B)
- WHEN the SaaS admin selects empresa A from the filter dropdown
- THEN exactly 6 users are displayed

#### Scenario: Default shows all users

- GIVEN the filter dropdown defaults to "All Empresas"
- WHEN the SaaS admin visits the user list without selecting a filter
- THEN all 10 users across both empresas are displayed
