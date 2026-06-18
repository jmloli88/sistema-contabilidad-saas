# Exam Default Seeding Specification

## Purpose

Automatic creation of 7 default exam records when a new empresa is registered. The seeding is event-driven and idempotent, preventing duplicate default exams.

## Requirements

### Requirement: Auto-Seeding on Empresa Creation

The system MUST automatically create 7 default exam records when a new Empresa is created.

#### Scenario: New empresa receives default exams

- GIVEN no existing empresa
- WHEN a new empresa is created
- THEN exactly 7 exam records are created associated with the new empresa
- AND each exam has a pre-defined name and standard prices
- AND all 7 exams have `is_active = true`

### Requirement: Idempotent Seeding

The system MUST NOT create duplicate default exams if the seeding process runs more than once for the same empresa.

#### Scenario: Second trigger does not duplicate exams

- GIVEN an empresa that already has 7 default exams
- WHEN the empresa created event is dispatched again
- THEN no additional exam records are created for that empresa
- AND the empresa still has exactly 7 exam records

#### Scenario: Manual exam added before re-trigger

- GIVEN an empresa with 7 default exams plus 1 manually created exam (8 total)
- WHEN the empresa created event is dispatched again
- THEN no additional exam records are created
- AND the empresa still has exactly 8 exam records
