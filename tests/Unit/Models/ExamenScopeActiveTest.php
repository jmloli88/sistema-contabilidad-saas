<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Empresa;
use App\Models\Examen;

class ExamenScopeActiveTest extends TestCase
{
    use RefreshDatabase;

    /**
     * REQ-ACTIVE-001: scopeActive() returns only exams with is_active = true
     */
    public function test_scope_active_returns_only_active_exams(): void
    {
        // GIVEN 3 exams exist: 2 active, 1 inactive
        // Use createQuietly to avoid auto-seeding from Empresa::created event
        $empresa = Empresa::factory()->createQuietly(['nombre' => 'Scope Active Test']);
        $examenActivoA = Examen::factory()->create([
            'empresa_id' => $empresa->id,
            'nombre' => 'EEG Activo A',
            'is_active' => true,
        ]);
        $examenActivoB = Examen::factory()->create([
            'empresa_id' => $empresa->id,
            'nombre' => 'EEG Activo B',
            'is_active' => true,
        ]);
        $examenInactivo = Examen::factory()->create([
            'empresa_id' => $empresa->id,
            'nombre' => 'EEG Inactivo',
            'is_active' => false,
        ]);

        // WHEN scopeActive() is applied
        $activeExams = Examen::active()->get();

        // THEN only the 2 active exams are returned
        $this->assertCount(2, $activeExams);
        $this->assertTrue($activeExams->contains('nombre', 'EEG Activo A'));
        $this->assertTrue($activeExams->contains('nombre', 'EEG Activo B'));
        $this->assertFalse($activeExams->contains('nombre', 'EEG Inactivo'));
    }

    /**
     * REQ-ACTIVE-002: scopeActive() returns empty when no active exams exist
     */
    public function test_scope_active_returns_empty_when_no_active_exams(): void
    {
        // GIVEN only inactive exams exist
        $empresa = Empresa::factory()->createQuietly(['nombre' => 'Scope Empty Test']);
        Examen::factory()->create([
            'empresa_id' => $empresa->id,
            'nombre' => 'EEG Inactivo',
            'is_active' => false,
        ]);
        Examen::factory()->create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Potencial Inactivo',
            'is_active' => false,
        ]);

        // WHEN scopeActive() is applied
        $activeExams = Examen::active()->get();

        // THEN no exams are returned
        $this->assertCount(0, $activeExams);
    }

    /**
     * REQ-ACTIVE-003: Without scopeActive(), all exams (active + inactive) are returned
     */
    public function test_without_scope_returns_all_exams(): void
    {
        // GIVEN 1 active and 1 inactive exam
        $empresa = Empresa::factory()->createQuietly(['nombre' => 'Scope All Test']);
        Examen::factory()->create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Activo',
            'is_active' => true,
        ]);
        Examen::factory()->create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Inactivo',
            'is_active' => false,
        ]);

        // WHEN no scope is applied (but global ScopedByEmpresa scope may be active)
        $allExams = Examen::withoutGlobalScopes()->where('empresa_id', $empresa->id)->get();

        // THEN both exams are returned
        $this->assertCount(2, $allExams);
    }
}
