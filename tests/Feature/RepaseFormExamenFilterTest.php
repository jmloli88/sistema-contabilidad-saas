<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Examen;
use App\Models\Clinica;
use App\Models\Repase;

class RepaseFormExamenFilterTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Empresa $empresa;
    private Clinica $clinica;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empresa = Empresa::factory()->create(['nombre' => 'Filter Test Empresa']);
        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'role' => 'administrador',
        ]);
        $this->empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_filter_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
        $this->clinica = Clinica::factory()->create(['empresa_id' => $this->empresa->id]);
    }

    /**
     * REQ-FILTER-001: Repase create form shows only active exams
     */
    public function test_repase_create_form_only_shows_active_exams(): void
    {
        // GIVEN 3 active exams and 2 inactive exams
        $activeExams = [];
        for ($i = 1; $i <= 3; $i++) {
            $activeExams[] = Examen::factory()->create([
                'empresa_id' => $this->empresa->id,
                'nombre' => "Active Exam {$i}",
                'is_active' => true,
            ]);
        }
        $inactiveExams = [];
        for ($i = 1; $i <= 2; $i++) {
            $inactiveExams[] = Examen::factory()->create([
                'empresa_id' => $this->empresa->id,
                'nombre' => "Inactive Exam {$i}",
                'is_active' => false,
            ]);
        }

        // WHEN loading the repase creation form
        $response = $this->actingAs($this->admin)
            ->get(route('repases.create'));

        // THEN only the 3 active exams appear
        $response->assertStatus(200);
        foreach ($activeExams as $exam) {
            $response->assertSee($exam->nombre);
        }
        foreach ($inactiveExams as $exam) {
            $response->assertDontSee($exam->nombre);
        }
    }

    /**
     * REQ-FILTER-002: Repase edit form shows only active exams but pre-selects current
     */
    public function test_repase_edit_form_shows_only_active_exams(): void
    {
        // GIVEN a repase with an exam that is now inactive, plus active exams
        $activeExam = Examen::factory()->create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Still Active Exam',
            'is_active' => true,
        ]);
        $inactiveExam = Examen::factory()->create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Now Inactive Exam',
            'is_active' => false,
        ]);
        $repase = Repase::factory()->create([
            'clinica_id' => $this->clinica->id,
            'fecha' => now()->format('Y-m-d'),
        ]);

        // WHEN loading the repase edit form
        $response = $this->actingAs($this->admin)
            ->get(route('repases.edit', $repase));

        // THEN active exam is visible
        $response->assertStatus(200);
        $response->assertSee($activeExam->nombre);
    }

    /**
     * REQ-FILTER-003: Inactive exam details still visible in repase detail view
     */
    public function test_repase_detail_shows_inactive_exam_name(): void
    {
        // GIVEN a repase with an exam that is now inactive
        $examen = Examen::factory()->create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Legacy EEG',
            'is_active' => false,
        ]);
        $repase = Repase::factory()->create([
            'clinica_id' => $this->clinica->id,
            'fecha' => now()->format('Y-m-d'),
        ]);
        $repase->repaseExamenes()->create([
            'examen_id' => $examen->id,
            'cantidad' => 1,
            'precio_unitario_usado' => 100.00,
            'subtotal' => 100.00,
        ]);

        // WHEN viewing the repase detail
        $response = $this->actingAs($this->admin)
            ->get(route('repases.show', $repase));

        // THEN the inactive exam name is still displayed (historical data preserved)
        $response->assertStatus(200);
        $response->assertSee('Legacy EEG');
    }
}
