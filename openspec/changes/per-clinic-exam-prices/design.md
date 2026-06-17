# Design: Per-Clinic Exam Prices

## Technical Approach

Pivot table `clinica_examen` between `clinicas` ↔ `examenes` with nullable price columns. Two-tier resolver method on `Examen` (override → global fallback). `RepaseService` accepts `$clinicaId` for price lookups. Collapsible UI section syncs pivot via `syncWithoutDetaching`.

## Architecture Decisions

### Decision: Pivot table over column explosion

**Choice**: Separate `clinica_examen(examen_id, clinica_id, precio_sin_nota, precio_con_nota)` pivot.
**Alternatives considered**: JSON column on `examenes`, separate `precios_por_clinica` table with non-FK structure.
**Rationale**: Pivot preserves referential integrity via FKs, supports `belongsToMany()->withPivot()`, and NULL semantics naturally model "no override." JSON would break queryability; separate table with text keys risks integrity.

### Decision: Resolver on Examen model, not a service

**Choice**: `Examen::getPrecioParaClinica(?int $clinicaId, string $tipoPrecio): float`
**Alternatives considered**: `PricingService` with dependency injection.
**Rationale**: Single point of truth on the model follows existing pattern (`calculateUtilizationStats`, `detectUtilizationAnomalies` on Examen). Teams already call `$examen->precio_sin_nota` — this replaces that property access with a method call that encapsulates the two-tier logic. No new class needed for a 5-line decision.

### Decision: No CHECK on pivot columns

**Choice**: Pivot `precio_sin_nota` and `precio_con_nota` are plain `DECIMAL(10,2) NULL`.
**Rationale**: Per proposal: no cross-column constraint on override prices. The global `examenes` CHECK (`sin_nota < con_nota`) is NOT replicated on the pivot — an override could have any value relationship.

## Price Resolution Flow

```
Controller/Service ──call──▶ $examen->getPrecioParaClinica($clinicaId, 'sin_nota')
                                     │
                          ┌──────────┴──────────┐
                          ▼                     ▼
                   clinicaId is null?    Load pivot:
                          │          Examen::clinicas()
                          │          ->wherePivot('precio_sin_nota', '!=', null)
                          │          ->find($clinicaId)
                          ▼                     │
                   return global         ┌──────┴──────┐
                   $this->precio_sin_nota │             │
                                   pivot found?    no pivot
                                         │             │
                                   return pivot     return global
                                   precio_sin_nota  $this->precio_sin_nota
```

## Repase Create Data Flow

```
POST /repases {clinica_id:3, tipo_precio:"sin_nota", examenes:[...]}
  │
  ▼
RepaseService::createRepase($data)
  │  $data['clinica_id'] = 3
  ▼
calculateTotalExamenes($examenes, 'sin_nota', clinicaId: 3)
  │  foreach examen:
  ▼
$examen->getPrecioParaClinica(3, 'sin_nota')
  │  checks clinica_examen pivot for (3, examen_id)
  │  returns override 150 or global 100
  ▼
precio_unitario_usado = 150 (snapshotted)
```

## File Changes

| File | Action | Description |
|------|--------|-------------|
| `database/migrations/XXXX_create_clinica_examen_table.php` | Create | Pivot table with composite PK, FK indexes |
| `app/Models/Examen.php` | Modify | +`clinicas()` belongsToMany, +`getPrecioParaClinica()`, +`getPreciosParaClinica()` |
| `app/Models/Clinica.php` | Modify | +`examenes()` belongsToMany |
| `app/Services/RepaseService.php` | Modify | 3 price lookups → `getPrecioParaClinica($clinicaId, ...)` |
| `app/Http/Controllers/ExamenController.php` | Modify | edit() loads clinics; update() syncs pivot; index() adds override count |
| `resources/views/examenes/edit.blade.php` | Modify | Collapsible `<details>` per-clinic table |
| `resources/views/examenes/index.blade.php` | Modify | Override count badge |
| `tests/` | Create/Modify | New unit + feature tests; update existing factory assertions |

## Key Signatures

```php
// Examen model (additions)
public function clinicas(): BelongsToMany
public function getPrecioParaClinica(?int $clinicaId, string $tipoPrecio): float
public function getPreciosParaClinica(?int $clinicaId): array  // ['sin_nota' => X, 'con_nota' => Y]

// Clinica model (addition)
public function examenes(): BelongsToMany

// RepaseService (signature change)
public function calculateTotalExamenes(array $examenes, string $tipoPrecio, ?int $clinicaId = null): float
```

## UI Wireframe — Edit View Section

```
┌─────────────────────────────────────────────────────────┐
│  Precio Sin Nota *  [input]                             │
│  Precio Con Nota *  [input]                             │
│                                                         │
│  ▶ Precios por Clínica (3 clínicas)                     │
│  ┌────────────────┬────────────────┬────────────────┐   │
│  │ Clínica        │ Precio Sin Nota│ Precio Con Nota│   │
│  ├────────────────┼────────────────┼────────────────┤   │
│  │ Centro A       │ [  150.00  ]   │ [  200.00  ]   │   │
│  │ Centro B       │ [          ]   │ [          ]   │   │
│  │ Centro C       │ [  120.00  ]   │ [          ]   │   │
│  └────────────────┴────────────────┴────────────────┘   │
│  (campos vacíos = usar precio global)                   │
│                                                         │
│  [Cancelar]  [Actualizar Precios]                       │
└─────────────────────────────────────────────────────────┘
```

## Migration Plan

1. Create `clinica_examen` migration (no data migration — zero existing overrides)
2. Seed optional: none needed, overrides created organically via UI
3. Rollback: drop table, remove relationships, revert service calls

## Testing Strategy

| Layer | What to Test | Approach |
|-------|-------------|----------|
| Unit | `getPrecioParaClinica` — override wins, fallback on null, fallback on missing pivot, null clinicaId | Create Examen + Clinica, attach pivot with `->clinicas()->attach()`, assert return value |
| Unit | `getPreciosParaClinica` — returns both prices | Same setup, assert array shape |
| Feature | `ExamenController::update` with per-clinic prices | POST with `precios_clinicas` array, assert pivot rows in DB |
| Feature | `RepaseService::createRepase` with override → `precio_unitario_usado` snapshots override | Create examen with pivot override, call createRepase, assert snapshot value |
| Existing | 46 tests must pass after model relationship additions | Run `php artisan test --parallel` |

## Open Questions

None — all decisions covered by proposal and spec.
