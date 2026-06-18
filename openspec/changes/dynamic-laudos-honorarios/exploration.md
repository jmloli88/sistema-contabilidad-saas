# Exploration: Dynamic "Honorarios Laudos" Fields

## Current State

### How the 3 Fixed Laudos Fields Work

The GASTOS OPERATIVOS section in both `create.blade.php` and `edit.blade.php` renders **3 hardcoded `<div>` blocks** for laudos honorarios:

| Label | `gasto_key` | `x-model` |
|-------|------------|-----------|
| Honorarios Laudos EGG | `honorarios_laudos_egg` | `gastos.honorarios_laudos_egg` |
| Honorarios Laudos Potencial | `honorarios_laudos_potencial` | `gastos.honorarios_laudos_potencial` |
| Honorarios Laudo Electromiografía | `honorarios_laudo_electromiografia` | `gastos.honorarios_laudo_electromiografia` |

### Alpine.js `gastos` Object

Both forms define a `gastos` object in the `repaseForm()` Alpine component with these 3 keys hardcoded as initial values (zero):

- **create** (lines 551–553): `honorarios_laudos_egg: parseFloat('{{ old('gastos.honorarios_laudos_egg', '0') }}')`, etc.
- **edit** (lines 476–478): `honorarios_laudos_egg: 0`, etc.

### `calcularTotalGastos()` — Already Generic

Both create/edit have the same implementation:

```js
calcularTotalGastos() {
    this.totalGastos = Object.values(this.gastos).reduce((sum, valor) => {
        return sum + (parseFloat(valor) || 0);
    }, 0);
    this.calcularTotalNeto();
}
```

This iterates ALL values of the `gastos` object — any dynamic keys added to `gastos` will be summed automatically. **No change needed here**.

### Backend Save: `RepaseService::normalizeGastos()`

The `$tipoMap` (lines 366–395) has entries for the 3 laudos:

```php
'honorarios_laudos_egg'           => ['tipo' => 'laudos', 'descripcion' => 'Honorarios Laudos EGG'],
'honorarios_laudos_potencial'     => ['tipo' => 'laudos', 'descripcion' => 'Honorarios Laudos Potencial'],
'honorarios_laudo_electromiografia' => ['tipo' => 'laudos', 'descripcion' => 'Honorarios Laudo Electromiografía'],
```

**IMPORTANT**: The method has an **else branch (lines 422–430)** that handles UNKNOWN keys:

```php
else {
    $normalized[] = [
        'tipo' => 'extra',                // ← WRONG for laudos — would miscategorize
        'descripcion' => ucfirst(str_replace('_', ' ', $tipoKey)),
        'gasto_key' => $tipoKey,
        'monto' => $montoFloat,
    ];
}
```

This means dynamic laudo keys would save with `tipo => 'extra'` instead of `tipo => 'laudos'`, breaking category display on the show blade.

### Edit Restoration — Already Dynamic

The edit blade restores gastos via a PHP loop over `$repase->gastos` (lines 542–599):

```php
@foreach($repase->gastos as $gasto)
    // ...
    @if($gastoKey)
        this.gastos.{{ $gastoKey }} = parseFloat({{ $gasto->monto }});
    @endif
@endforeach
```

This is **already dynamic** — any `gasto_key` value maps directly to a property on `this.gastos`. No change needed for restoration.

The description-based fallback mapping (lines 548–577) is only for old records that lack a `gasto_key`. New records always have `gasto_key` set by `normalizeGastos()`.

### Show Blade — Tipo-Based Filtering

The show blade (line 268) categorizes gastos by `tipo`:

```php
$gastosOperativos = $repase->gastos->filter(function($g) {
    return in_array($g->tipo, ['doctor', 'tecnico', 'laudos', 'gasolina']) && ...;
});
```

If a laudo saves with `tipo => 'extra'` (from the fallback), it would NOT appear under "Gastos Operativos". This is the key risk.

### Request Validation — Already Dynamic

`StoreRepaseRequest` (line 42): `'gastos.*' => 'nullable|numeric|min:0'` — accepts any key. **No change needed**.

### Exam Data Flow

The `@examenes` passed to both views comes from `RepaseController::create/edit`:
```php
$examenes = Examen::active()->select('id', 'nombre', ...)->orderBy('nombre')->get();
```

This — combined with `dynamic-exam-management` — means `examenesDisponibles` already includes ALL active exams (including custom ones like "MINIMENTAL"), because the controller's query uses `->active()` which filters `is_active = true`.

---

## What Needs to Change

### 1. Alpine.js Data Initialization (create + edit)

Replace the 3 hardcoded keys in the `gastos` object with **dynamically generated keys** from `examenesDisponibles`.

**Approach**: Iterate `examenesDisponibles` in `init()` and add `gastos[laudoKey]` for each active exam.

```js
init() {
    // ... existing init code ...
    
    // Dynamic laudos: create a gasto entry for each active exam
    this.examenesDisponibles.forEach(examen => {
        const key = 'honorarios_laudo_examen_' + examen.id;
        if (this.gastos[key] === undefined) {
            this.gastos[key] = 0;
        }
    });
}
```

### 2. View Template — Render Dynamic Laudo Fields (create + edit)

Replace the 3 hardcoded `<div>` blocks with an Alpine `x-for` loop inside the GASTOS OPERATIVOS grid:

```html
<template x-for="examen in examenesDisponibles" :key="'laudo-' + examen.id">
    <div>
        <label class="block text-sm font-medium text-gray-700 mb-1" 
               x-text="'Honorarios Laudos ' + examen.nombre"></label>
        <input type="number" step="0.01" min="0" 
               :x-model.number="'gastos.honorarios_laudo_examen_' + examen.id"
               :name="'gastos[honorarios_laudo_examen_' + examen.id + ']'"
               @input="calcularTotalGastos"
               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>
</template>
```

**Note**: Alpine `x-model` with dynamic expressions requires a computed getter/setter or a wrapper. A cleaner approach may be to use `@input` handlers that update `gastos[key]` manually instead of `x-model.number`.

### 3. RepaseService — Add Dynamic Key Handling

In `normalizeGastos()`, add a check for the `honorarios_laudo_examen_` prefix **BEFORE** the fallback else:

```php
// Before the generic else (around line 421)
if (str_starts_with($tipoKey, 'honorarios_laudo_')) {
    $examenId = (int) str_replace('honorarios_laudo_examen_', '', $tipoKey);
    $examen = Examen::find($examenId);
    $descripcion = $examen ? "Honorarios Laudos {$examen->nombre}" : ucfirst(str_replace('_', ' ', $tipoKey));
    
    $normalized[] = [
        'tipo' => 'laudos',
        'descripcion' => $descripcion,
        'gasto_key' => $tipoKey,
        'monto' => $montoFloat,
    ];
}
```

### 4. Edit Restoration — Gasto Key Mapping for Old Records

The description-based mapping (lines 548–577) already handles the 3 old laudo descriptions. **No change needed** since old records still have `gasto_key` set.

However, if there are records from BEFORE the `gasto_key` migration, the description matching is already correct.

### 5. Show Blade — No Change Needed

The show blade filters by `$g->tipo === 'laudos'`. As long as `normalizeGastos()` sets `tipo => 'laudos'`, dynamic laudo fields will appear correctly.

---

## Naming Convention for Dynamic `gasto_key`

**Recommended**: `honorarios_laudo_examen_{examen_id}`

**Rationale**:
- **Unambiguous**: The `examen_id` ties directly to the `examenes` table — no slugification or name-parsing needed
- **Immutable**: If an exam name changes, the gasto_key remains valid (unlike name-based keys)
- **Traceable**: You can join back to the Examen model to get the name at save time
- **Extensions**: The `normalizeGastos()` prefix check (`str_starts_with('honorarios_laudo_')`) is easy to maintain

**The 3 existing keys** (`honorarios_laudos_egg`, `honorarios_laudos_potencial`, `honorarios_laudo_electromiografia`) must remain in the `$tipoMap` for backward compatibility with existing records.

---

## Affected Files

| File | Lines | What Changes |
|------|-------|-------------|
| `resources/views/repases/create.blade.php` | 274–290, 546–556, 606–626 | Replace 3 hardcoded laudo `<div>` blocks with Alpine `x-for`; dynamically initialize `gastos` keys in `init()`; update old() restoration |
| `resources/views/repases/edit.blade.php` | 198–215, 471–500, 526–622 | Same structural changes as create blade |
| `app/Services/RepaseService.php` | 366–430 | Add `str_starts_with('honorarios_laudo_')` handling in `normalizeGastos()` before fallback; keep existing 3 keys in `$tipoMap` |
| `resources/views/repases/show.blade.php` | 267–269 | **No change needed** — but verify `$g->tipo === 'laudos'` continues to work |
| `app/Http/Requests/StoreRepaseRequest.php` | 42 | **No change needed** — `'gastos.*'` already accepts any key |

---

## Risks

### 1. Backward Compatibility with Existing Repases

- **HIGH**: Existing repases have `gasto_key` values of the 3 old keys. These must continue to work.
- **Mitigation**: Keep the 3 old keys in `$tipoMap` in RepaseService. The edit restoration loop (`this.gastos.{{ $gastoKey }}`) is already dynamic and will load any key.
- **Remaining risk**: If a repase from before the `gasto_key` migration exists (description-based only), the edit restoration's description matching (lines 556–558) must remain intact.

### 2. Description Consistency on New Keys

- **MEDIUM**: The old keys use mixed plural/singular: `honorarios_laudos_egg`, `honorarios_laudos_potencial`, `honorarios_laudo_electromiografia`. The new convention uses singular `honorarios_laudo_examen_{id}`.
- **Mitigation**: The description stored in the `descripcion` column is separate from the key — use the exam's current name for the description at save time.

### 3. Exam Deletion / Deactivation

- **MEDIUM**: If an exam is deactivated (`is_active = false`) after repases reference it via `honorarios_laudo_examen_{id}`, the laudo gasto_key still works — but the edit form won't render a field for the deactivated exam.
- **Mitigation**: The edit restoration still sets `this.gastos[key] = value` from the DB, so the data is preserved and included in totals even if no visible field exists. This is acceptable behavior.

### 4. Exam Name Changes

- **LOW**: If an exam name changes, existing repases with `honorarios_laudo_examen_{id}` still work — the key references the ID, not the name. The description was frozen at save time.

### 5. Alpine `x-model` with Dynamic Expressions

- **MEDIUM**: Alpine.js `x-model` does NOT support dynamic property paths like `'gastos.' + key`. A `@input` handler approach or a computed wrapper is needed.
- **Alternative**: Use a `@input` event that computes the key from the exam ID:
  ```html
  @input="gastos['honorarios_laudo_examen_' + examen.id] = $event.target.value; calcularTotalGastos()"
  ```

---

## Readiness for Proposal

**Yes**, ready for proposal.

The change is well-scoped: it touches 4 files, follows the existing patterns (`gasto_key` convention, Alpine component architecture, `$tipoMap` in RepaseService), and the backward compatibility story is clear.

The orchestrator should tell the user:
- The 3 hardcoded laudo fields will be replaced by a dynamic loop over `examenesDisponibles`
- New naming convention: `honorarios_laudo_examen_{id}`
- Existing repases with old keys will continue to work unchanged
- The `normalizeGastos()` fallback will detect the `honorarios_laudo_` prefix and assign `tipo => 'laudos'` correctly
- Show blade categorizes by `tipo`, so new laudos appear automatically
- The `calcularTotalGastos()` method and request validation need no changes
