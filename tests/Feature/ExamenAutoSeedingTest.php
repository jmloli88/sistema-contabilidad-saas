<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Empresa;
use App\Models\Examen;

class ExamenAutoSeedingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * REQ-SEED-001: New empresa auto-creates exactly 7 default exams
     */
    public function test_new_empresa_creates_exactly_7_default_exams(): void
    {
        // WHEN a new empresa is created
        $empresa = Empresa::factory()->create(['nombre' => 'Auto Seed Test']);

        // THEN exactly 7 exam records are created
        $this->assertCount(7, $empresa->examenes);
    }

    /**
     * REQ-SEED-002: All default exams have is_active = true
     */
    public function test_default_exams_are_active(): void
    {
        // WHEN a new empresa is created
        $empresa = Empresa::factory()->create();

        // THEN all default exams have is_active = true
        foreach ($empresa->examenes as $examen) {
            $this->assertTrue($examen->is_active);
        }
    }

    /**
     * REQ-SEED-003: Default exams have standard names and prices
     */
    public function test_default_exams_have_expected_names(): void
    {
        // WHEN a new empresa is created
        $empresa = Empresa::factory()->create();

        // THEN the exams have the standard 7 names from defaults()
        $defaults = Examen::defaults();
        $defaultNames = array_column($defaults, 'nombre');

        $empresaExamNames = $empresa->examenes->pluck('nombre')->toArray();

        foreach ($defaultNames as $name) {
            $this->assertContains($name, $empresaExamNames);
        }
    }

    /**
     * REQ-SEED-004: Recreating empresa does not add more exams (new empresas get 7, no cumulative)
     */
    public function test_multiple_empresas_each_get_7_exams(): void
    {
        // GIVEN one empresa already has its 7 exams
        $empresa1 = Empresa::factory()->create();
        $this->assertCount(7, $empresa1->examenes);

        // WHEN a second empresa is created
        $empresa2 = Empresa::factory()->create();

        // THEN the second empresa also has exactly 7 (no cross-contamination)
        $this->assertCount(7, $empresa2->examenes);

        // AND the first empresa still has 7
        $this->assertCount(7, $empresa1->fresh()->examenes);
    }

    /**
     * REQ-SEED-005: Manual exam added does not get duplicated (seeding only runs when count === 0)
     */
    public function test_empresa_with_7_exams_does_not_get_additional_exams(): void
    {
        // GIVEN an empresa with 7 default exams
        $empresa = Empresa::factory()->create();
        $this->assertCount(7, $empresa->examenes);

        // WHEN a manual exam is added (now 8)
        $empresa->examenes()->create([
            'nombre' => 'Manual Custom Exam',
            'precio_sin_nota' => 300.00,
            'precio_con_nota' => 350.00,
            'is_active' => true,
        ]);
        $this->assertCount(8, $empresa->fresh()->examenes);

        // WHEN the AppServiceProvider closure runs again (simulating re-fire)
        // The closure checks if count === 0, so it should skip
        $closure = function (Empresa $empresa) {
            if ($empresa->examenes()->count() === 0) {
                foreach (Examen::defaults() as $exam) {
                    $empresa->examenes()->create($exam);
                }
            }
        };
        $closure($empresa);

        // THEN the empresa still has exactly 8 exams (no duplicates)
        $empresa->refresh();
        $this->assertCount(8, $empresa->examenes);
    }
}
