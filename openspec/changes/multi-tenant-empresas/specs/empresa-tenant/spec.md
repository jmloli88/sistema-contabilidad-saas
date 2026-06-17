# Empresa Tenant Specification

## Purpose

Define the `Empresa` model as the multi-tenant anchor entity. Each empresa owns isolated sets of clinicas, users, examenes, subscriptions, and prediction configurations.

## Requirements

### Requirement: Empresa Schema

The system MUST persist empresas with a single `empresas` table containing: `id` (PK auto-increment), `nombre` (VARCHAR 255 NOT NULL), `slug` (VARCHAR 255 UNIQUE), `email` (VARCHAR 255 NULLABLE), `telefono` (VARCHAR 20 NULLABLE), `direccion` (TEXT NULLABLE), `is_active` (BOOLEAN DEFAULT TRUE), `settings` (JSON NULLABLE), `created_at`, `updated_at`.

#### Scenario: Create empresa with required fields

- GIVEN a SaaS admin is authenticated
- WHEN they submit `nombre` and unique `slug`
- THEN a new empresa record is persisted with `is_active = true`
- AND the record is assigned an auto-increment `id`

#### Scenario: Slug uniqueness enforcement

- GIVEN an existing empresa with slug "zumed-dg"
- WHEN another empresa is created with slug "zumed-dg"
- THEN the database MUST reject the insert with a uniqueness violation

### Requirement: Empresa Eloquent Relationships

The `Empresa` model SHALL define `HasMany` relationships to `Clinica`, `User`, `Examen`, `Subscription`, and `PredictionConfiguration`. Reverse `BelongsTo` relationships MUST be present on each child model via the `empresa_id` foreign key.

#### Scenario: Retrieve all clinicas of an empresa

- GIVEN empresa A has 3 clinicas and empresa B has 2 clinicas
- WHEN `$empresaA->clinicas` is accessed
- THEN exactly 3 clinica records are returned
- AND none belong to empresa B

#### Scenario: User belongs to empresa

- GIVEN a user assigned `empresa_id = 1`
- WHEN `$user->empresa` is accessed
- THEN the empresa with `id = 1` is returned

### Requirement: Seed Migration

The system MUST execute a data migration that creates one seed empresa (slug "zumeddg", nombre "Zumed Medicina Diagnóstica") and assigns all existing `clinicas`, `users`, and `examenes` to it via `empresa_id`. This migration SHALL run after the `empresa_id` nullable columns are added but before NOT NULL constraints are enforced.

#### Scenario: Migrate existing production data

- GIVEN 5 clinicas, 20 users, and 100 examenes exist with NULL `empresa_id`
- WHEN the seed migration runs
- THEN a single seed empresa is created
- AND all existing records receive that empresa's `id` in their `empresa_id` column
- AND no data is lost

#### Scenario: Seed migration is idempotent

- GIVEN the seed migration has already been executed
- WHEN it is attempted again
- THEN it MUST be a no-op (check for existing seed empresa before inserting)
