# Exam Management Specification

## Purpose

Lifecycle management for `examenes` — create, edit, activate/deactivate, and conditional hard-delete. Active-only filtering for repase forms and exam listings.

## Requirements

### Requirement: Exam CRUD Operations

The system MUST allow administrators to create, edit, and delete exam records.

#### Scenario: Create a new exam

- GIVEN an authenticated administrator
- WHEN they submit a form with name, precio_sin_nota, and precio_con_nota
- THEN a new exam is created with `is_active = true`
- AND the exam appears in the active exam listing

#### Scenario: Edit an existing exam

- GIVEN an existing exam record
- WHEN an administrator updates its name, precio_sin_nota, or precio_con_nota
- THEN the exam record is updated
- AND historical repase records referencing this exam remain unchanged

### Requirement: Exam Activation/Deactivation Toggle

The system MUST support toggling an exam's `is_active` flag. Deactivated exams SHALL be excluded from repase form selection grids but SHALL remain accessible for historical data.

#### Scenario: Deactivate an active exam

- GIVEN an active exam (`is_active = true`)
- WHEN an administrator toggles its status
- THEN the exam becomes inactive (`is_active = false`)
- AND the exam no longer appears in repase create/edit form exam grids

#### Scenario: Reactivate a deactivated exam

- GIVEN an inactive exam (`is_active = false`)
- WHEN an administrator toggles its status
- THEN the exam becomes active (`is_active = true`)
- AND the exam reappears in repase create/edit form exam grids

#### Scenario: Historical repase displays deactivated exam

- GIVEN a repase record that references a now-deactivated exam
- WHEN viewing the repase detail page
- THEN the deactivated exam name is displayed correctly
- AND an inactive badge is shown next to the exam name

### Requirement: Active-Only Filtering in Repase Forms

The system MUST constrain repase create and edit forms to display only active exams.

#### Scenario: Repase create form filters active exams

- GIVEN 5 active exams and 2 inactive exams
- WHEN loading the repase creation form
- THEN only the 5 active exams appear in the exam selection grid

#### Scenario: Repase edit form filters active exams

- GIVEN a repase record with an active exam and 2 other inactive exams exist
- WHEN loading the repase edit form
- THEN the currently assigned exam is pre-selected
- AND only active exams appear as alternatives in the selection grid

### Requirement: Conditional Hard-Delete

The system MUST allow hard-deletion of exams only when no historical repase records reference the exam. If references exist, the system SHALL block the deletion.

#### Scenario: Hard-delete exam with no history

- GIVEN an exam with zero associated repase_examenes records
- WHEN an administrator requests deletion
- THEN the exam is permanently removed from the database
- AND a success confirmation is shown

#### Scenario: Block deletion of exam with history

- GIVEN an exam with one or more associated repase_examenes records
- WHEN an administrator requests deletion
- THEN the deletion is blocked
- AND an error message is displayed indicating historical data exists
- AND the exam remains in the database
