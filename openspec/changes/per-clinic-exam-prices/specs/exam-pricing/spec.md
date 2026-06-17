# exam-pricing Specification

## Purpose

Two-tier exam price resolution: per-clinic overrides in `clinica_examen` pivot (nullable)
fall back to global `examenes.precio_*` when no override exists.

## Requirements

| ID | Requirement | RFC 2119 |
|----|------------|----------|
| REQ-PRICE-001 | Pivot `clinica_examen(clinica_id, examen_id, precio_sin_nota DECIMAL(10,2) NULL, precio_con_nota DECIMAL(10,2) NULL)` stores per-clinic overrides. NULL means "use global". | MUST |
| REQ-PRICE-002 | `Examen::getPrecioParaClinica($clinicaId, $tipoPrecio)` resolves price: pivot non-null value wins, else `$this->{"precio_$tipoPrecio"}`. | MUST |
| REQ-PRICE-003 | Missing pivot row SHALL return the global `examenes.precio_*` value. Pivot row with NULL price SHALL also fall back to global. | SHALL |
| REQ-PRICE-004 | `RepaseService` create, update, and `calculateTotalExamenes` MUST accept `$clinicaId` and call `getPrecioParaClinica()` instead of direct `$examen->precio_*`. | MUST |
| REQ-PRICE-005 | Exam edit view SHALL display collapsible "Precios por Clínica" table. Controller MUST sync pivot overrides on store/update; empty inputs become NULL. | SHALL |
| REQ-PRICE-006 | Exam index SHALL show badge with override count per exam. Zero overrides SHALL display no badge. | SHALL |
| REQ-PRICE-007 | Historical `repase_examenes.precio_unitario_usado` MUST NOT be recalculated. New records SHALL snapshot the resolved price at creation time. | MUST |

## Scenarios

### REQ-PRICE-001: Override Storage

- GIVEN Clinica A and Examen X with global precio_sin_nota=100
- WHEN saving override precio_sin_nota=150 for Clinica A
- THEN pivot stores (clinica_id=A, examen_id=X, precio_sin_nota=150, precio_con_nota=NULL)

- GIVEN Override input is empty/blank
- WHEN saving
- THEN pivot column stores NULL (fallback to global)

### REQ-PRICE-002: Override Wins

- GIVEN Pivot has precio_sin_nota=150 for clinic+exam
- WHEN getPrecioParaClinica(clinicId, 'sin_nota')
- THEN returns 150

### REQ-PRICE-003: Fallback to Global

- GIVEN No pivot row for clinic+exam
- WHEN getPrecioParaClinica(clinicId, 'sin_nota')
- THEN returns examen.precio_sin_nota

- GIVEN Pivot row exists with precio_sin_nota=NULL
- WHEN getPrecioParaClinica(clinicId, 'sin_nota')
- THEN returns examen.precio_sin_nota

### REQ-PRICE-004: Repase Price Lookup

- GIVEN Examen X global=100, Clinica A override=150
- WHEN creating repase for Clinica A with X, tipo_precio=sin_nota
- THEN precio_unitario_usado=150

- GIVEN Examen X global=100, Clinica B has no override
- WHEN creating repase for Clinica B with X, tipo_precio=sin_nota
- THEN precio_unitario_usado=100

- GIVEN Repase has 3 examenes at mixed prices
- WHEN calculateTotalExamenes called with repase's clinica_id
- THEN total uses clinic-resolved price per examen

### REQ-PRICE-005: UI Management

- GIVEN 3 clinics exist
- WHEN viewing exam edit page
- THEN collapsible table shows 3 rows: clinic name + two price inputs

- GIVEN Per-clinic overrides submitted in edit form
- WHEN saving exam
- THEN pivot reflects submitted values; blanks become NULL

### REQ-PRICE-006: Index Indicator

- GIVEN Examen X has overrides for 2 of 5 clinics
- WHEN viewing exam index
- THEN badge displays "2" near exam name

- GIVEN Examen Y has zero overrides
- WHEN viewing exam index
- THEN no badge appears

### REQ-PRICE-007: Historical Isolation

- GIVEN repase_examen with precio_unitario_usado=100 from 2024
- WHEN per-clinic prices are changed
- THEN historical price remains 100

- GIVEN New repase for clinica with current override=150
- WHEN repase is created
- THEN precio_unitario_usado snapshots 150
