<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Clinica;
use App\Models\Examen;
use App\Models\Repase;
use App\Services\RepaseService;

class RepaseServiceTest extends TestCase
{
    use RefreshDatabase;

    private RepaseService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RepaseService();
    }

    /**
     * Helper to build createRepase data array.
     */
    private function makeCreateData(?int $clinicaId, Examen $examen, string $tipoPrecio = 'sin_nota', int $cantidad = 1): array
    {
        return [
            'clinica_id' => $clinicaId,
            'fecha' => '2026-06-16',
            'fecha_pago' => null,
            'estado' => 'pendiente',
            'tipo_precio' => $tipoPrecio,
            'examenes' => [
                $examen->id => [
                    'examen_id' => $examen->id,
                    'cantidad' => $cantidad,
                ],
            ],
            'total_consultas' => 0,
            'pedidos_doctor' => 0,
            'observaciones' => null,
            'comentarios' => [
                'operativos' => null,
                'administrativos' => null,
                'caja_chica' => null,
                'insumios_medicos' => null,
            ],
        ];
    }

    // ────────────────────────────────────────────────────────────
    // REQ-PRICE-004, REQ-PRICE-007: createRepase price resolution
    // ────────────────────────────────────────────────────────────

    public function test_create_repase_with_clinic_override_snapshots_override_price(): void
    {
        // GIVEN Examen X global=100, Clinica A override=150
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 200.00,
        ]);
        $clinica = Clinica::factory()->create();
        $examen->clinicas()->attach($clinica->id, [
            'precio_sin_nota' => 150.00,
            'precio_con_nota' => 250.00,
        ]);

        // WHEN creating repase for Clinica A with X, tipo_precio=sin_nota
        $repase = $this->service->createRepase(
            $this->makeCreateData($clinica->id, $examen, 'sin_nota')
        );

        // THEN precio_unitario_usado=150 (override wins)
        $repase->load('repaseExamenes');
        $this->assertCount(1, $repase->repaseExamenes);
        $this->assertSame(150.00, (float) $repase->repaseExamenes->first()->precio_unitario_usado);
    }

    public function test_create_repase_without_override_uses_global_price(): void
    {
        // GIVEN Examen X global=100, Clinica B has no override
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 200.00,
        ]);
        $clinica = Clinica::factory()->create();
        // No attach — no pivot row

        // WHEN creating repase for Clinica B
        $repase = $this->service->createRepase(
            $this->makeCreateData($clinica->id, $examen, 'sin_nota')
        );

        // THEN precio_unitario_usado=100 (global fallback)
        $repase->load('repaseExamenes');
        $this->assertCount(1, $repase->repaseExamenes);
        $this->assertSame(100.00, (float) $repase->repaseExamenes->first()->precio_unitario_usado);
    }

    public function test_create_repase_with_null_pivot_uses_global_price(): void
    {
        // REQ-PRICE-003: GIVEN Pivot row exists with precio_sin_nota=NULL
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 200.00,
        ]);
        $clinica = Clinica::factory()->create();
        $examen->clinicas()->attach($clinica->id, [
            'precio_sin_nota' => null,
            'precio_con_nota' => null,
        ]);

        // WHEN creating repase with clinica_id (pivot has NULL prices)
        $repase = $this->service->createRepase(
            $this->makeCreateData($clinica->id, $examen, 'sin_nota')
        );

        // THEN precio_unitario_usado=100 (global fallback because pivot is NULL)
        $repase->load('repaseExamenes');
        $this->assertCount(1, $repase->repaseExamenes);
        $this->assertSame(100.00, (float) $repase->repaseExamenes->first()->precio_unitario_usado);
    }

    public function test_create_repase_with_override_precio_con_nota(): void
    {
        // GIVEN Examen X global=200, Clinica A override=250 for con_nota
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 200.00,
        ]);
        $clinica = Clinica::factory()->create();
        $examen->clinicas()->attach($clinica->id, [
            'precio_sin_nota' => null,
            'precio_con_nota' => 250.00,
        ]);

        // WHEN creating repase with tipo_precio=con_nota
        $repase = $this->service->createRepase(
            $this->makeCreateData($clinica->id, $examen, 'con_nota')
        );

        // THEN precio_unitario_usado=250 (con_nota override wins)
        $repase->load('repaseExamenes');
        $this->assertCount(1, $repase->repaseExamenes);
        $this->assertSame(250.00, (float) $repase->repaseExamenes->first()->precio_unitario_usado);
    }

    // ────────────────────────────────────────────────────────────
    // REQ-PRICE-004: updateRepase price resolution
    // ────────────────────────────────────────────────────────────

    public function test_update_repase_with_override_snapshots_override_price(): void
    {
        // GIVEN Examen X global=100, Clinica A override=150
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 200.00,
        ]);
        $clinica = Clinica::factory()->create();
        $examen->clinicas()->attach($clinica->id, [
            'precio_sin_nota' => 150.00,
            'precio_con_nota' => 250.00,
        ]);

        // Create an existing repase first
        $existingRepase = Repase::factory()->create([
            'clinica_id' => $clinica->id,
        ]);

        // WHEN updating repase with new exam data
        $updated = $this->service->updateRepase($existingRepase, [
            'clinica_id' => $clinica->id,
            'fecha' => '2026-06-16',
            'fecha_pago' => null,
            'estado' => 'pendiente',
            'tipo_precio' => 'sin_nota',
            'examenes' => [
                $examen->id => [
                    'examen_id' => $examen->id,
                    'cantidad' => 2,
                ],
            ],
            'total_consultas' => 0,
            'pedidos_doctor' => 0,
            'observaciones' => null,
            'comentarios' => [
                'operativos' => null,
                'administrativos' => null,
                'caja_chica' => null,
                'insumios_medicos' => null,
            ],
        ]);

        // THEN precio_unitario_usado=150 (override wins)
        $updated->load('repaseExamenes');
        $this->assertCount(1, $updated->repaseExamenes);
        $this->assertSame(150.00, (float) $updated->repaseExamenes->first()->precio_unitario_usado);
    }

    public function test_update_repase_without_override_uses_global_price(): void
    {
        // GIVEN Examen X global=100, Clinica B has no override
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 200.00,
        ]);
        $clinica = Clinica::factory()->create();

        $existingRepase = Repase::factory()->create([
            'clinica_id' => $clinica->id,
        ]);

        // WHEN updating repase
        $updated = $this->service->updateRepase($existingRepase, [
            'clinica_id' => $clinica->id,
            'fecha' => '2026-06-16',
            'fecha_pago' => null,
            'estado' => 'pendiente',
            'tipo_precio' => 'sin_nota',
            'examenes' => [
                $examen->id => [
                    'examen_id' => $examen->id,
                    'cantidad' => 1,
                ],
            ],
            'total_consultas' => 0,
            'pedidos_doctor' => 0,
            'observaciones' => null,
            'comentarios' => [
                'operativos' => null,
                'administrativos' => null,
                'caja_chica' => null,
                'insumios_medicos' => null,
            ],
        ]);

        // THEN precio_unitario_usado=100 (global fallback)
        $updated->load('repaseExamenes');
        $this->assertCount(1, $updated->repaseExamenes);
        $this->assertSame(100.00, (float) $updated->repaseExamenes->first()->precio_unitario_usado);
    }

    // ────────────────────────────────────────────────────────────
    // REQ-PRICE-004: calculateTotalExamenes with clinic override
    // ────────────────────────────────────────────────────────────

    public function test_calculate_total_examenes_with_override_uses_clinic_prices(): void
    {
        // GIVEN Examen X global=100, Clinica A override=150
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 200.00,
        ]);
        $clinica = Clinica::factory()->create();
        $examen->clinicas()->attach($clinica->id, [
            'precio_sin_nota' => 150.00,
            'precio_con_nota' => 250.00,
        ]);

        // WHEN calculateTotalExamenes called with clinica_id
        $total = $this->service->calculateTotalExamenes(
            [
                ['examen_id' => $examen->id, 'cantidad' => 3],
            ],
            'sin_nota',
            $clinica->id
        );

        // THEN total=450 (3 × 150 override)
        $this->assertSame(450.00, $total);
    }

    public function test_calculate_total_examenes_mixed_prices(): void
    {
        // GIVEN Examen A global=100 with override=150, Examen B global=200 without override
        $examenA = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 200.00,
        ]);
        $examenB = Examen::factory()->create([
            'precio_sin_nota' => 200.00,
            'precio_con_nota' => 300.00,
        ]);
        $clinica = Clinica::factory()->create();
        $examenA->clinicas()->attach($clinica->id, [
            'precio_sin_nota' => 150.00,
            'precio_con_nota' => 250.00,
        ]);
        // Examen B has no pivot — uses global

        // WHEN calculateTotalExamenes called with clinica_id
        $total = $this->service->calculateTotalExamenes(
            [
                ['examen_id' => $examenA->id, 'cantidad' => 2],
                ['examen_id' => $examenB->id, 'cantidad' => 3],
            ],
            'sin_nota',
            $clinica->id
        );

        // THEN total = (2 × 150) + (3 × 200) = 300 + 600 = 900
        $this->assertSame(900.00, $total);
    }

    // ────────────────────────────────────────────────────────────
    // Dynamic laudo honorarios — normalizeGastos prefix handler
    // ────────────────────────────────────────────────────────────

    public function test_dynamic_laudo_key_normalizes_to_laudos_tipo(): void
    {
        // GIVEN an active examen exists
        $examen = Examen::factory()->create([
            'nombre' => 'EEG',
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 200.00,
        ]);
        $clinica = Clinica::factory()->create();

        // WHEN creating a repase with a dynamic laudo gasto key
        $data = $this->makeCreateData($clinica->id, $examen);
        $data['gastos'] = [
            'honorarios_laudo_examen_' . $examen->id => 200.00,
        ];

        $repase = $this->service->createRepase($data);

        // THEN the gasto row has tipo='laudos', descripcion includes the exam name
        $repase->load('gastos');
        $this->assertCount(1, $repase->gastos);
        $gasto = $repase->gastos->first();
        $this->assertSame('laudos', $gasto->tipo);
        $this->assertSame('Honorarios Laudos EEG', $gasto->descripcion);
        $this->assertSame('honorarios_laudo_examen_' . $examen->id, $gasto->gasto_key);
        $this->assertSame(200.00, (float) $gasto->monto);
    }

    public function test_legacy_keys_still_resolve_via_tipo_map(): void
    {
        // GIVEN an examen and clinica
        $examen = Examen::factory()->create();
        $clinica = Clinica::factory()->create();

        // WHEN creating a repase with legacy laudo keys
        $data = $this->makeCreateData($clinica->id, $examen);
        $data['gastos'] = [
            'honorarios_laudos_egg' => 100.00,
            'honorarios_laudos_potencial' => 150.00,
            'honorarios_laudo_electromiografia' => 200.00,
        ];

        $repase = $this->service->createRepase($data);

        // THEN all rows have tipo='laudos'
        $repase->load('gastos');
        $this->assertCount(3, $repase->gastos);
        foreach ($repase->gastos as $gasto) {
            $this->assertSame('laudos', $gasto->tipo);
        }
    }

    public function test_unknown_key_falls_back_to_extra_tipo(): void
    {
        // GIVEN an examen and clinica
        $examen = Examen::factory()->create();
        $clinica = Clinica::factory()->create();

        // WHEN creating a repase with an unknown custom gasto key
        $data = $this->makeCreateData($clinica->id, $examen);
        $data['gastos'] = [
            'custom_fee' => 50.00,
        ];

        $repase = $this->service->createRepase($data);

        // THEN it falls back to tipo='extra'
        $repase->load('gastos');
        $this->assertCount(1, $repase->gastos);
        $gasto = $repase->gastos->first();
        $this->assertSame('extra', $gasto->tipo);
        $this->assertSame('Custom fee', $gasto->descripcion);
    }

    public function test_calculate_total_examenes_without_clinica_uses_global(): void
    {
        // GIVEN Examen X global=100
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 200.00,
        ]);

        // WHEN calculateTotalExamenes called without clinica_id
        $total = $this->service->calculateTotalExamenes(
            [
                ['examen_id' => $examen->id, 'cantidad' => 5],
            ],
            'sin_nota'
        );

        // THEN total=500 (5 × 100 global)
        $this->assertSame(500.00, $total);
    }
}
