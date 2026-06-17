<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Clinica;
use App\Models\Examen;

class ExamenPrecioClinicaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create([
            'role' => 'administrador',
        ]);
        // Give admin a subscription so subscription middleware passes
        $this->admin->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_admin_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // REQ-PRICE-005: Edit page includes clinics in view data
    // ────────────────────────────────────────────────────────────

    public function test_edit_page_includes_clinicas_in_view_data(): void
    {
        // GIVEN 3 clinics exist
        $clinicaA = Clinica::factory()->create(['nombre' => 'Centro Alpha']);
        $clinicaB = Clinica::factory()->create(['nombre' => 'Centro Beta']);
        $clinicaC = Clinica::factory()->create(['nombre' => 'Centro Gamma']);
        $examen = Examen::factory()->create();

        // WHEN viewing exam edit page
        $response = $this->actingAs($this->admin)
            ->get(route('examenes.edit', $examen));

        // THEN response has clinicas view data with all clinics ordered by name
        $response->assertStatus(200);
        $response->assertViewHas('clinicas');
        $clinicas = $response->viewData('clinicas');
        $this->assertCount(3, $clinicas);
        $this->assertEquals('Centro Alpha', $clinicas[0]->nombre);
        $this->assertEquals('Centro Beta', $clinicas[1]->nombre);
        $this->assertEquals('Centro Gamma', $clinicas[2]->nombre);
    }

    // ────────────────────────────────────────────────────────────
    // REQ-PRICE-005: Saving per-clinic prices persists pivot
    // ────────────────────────────────────────────────────────────

    public function test_guardar_precios_por_clinica_persiste_en_pivot(): void
    {
        // GIVEN 2 clinics and 1 examen exist
        $clinicaA = Clinica::factory()->create();
        $clinicaB = Clinica::factory()->create();
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 200.00,
        ]);

        // WHEN saving per-clinic overrides via update
        $response = $this->actingAs($this->admin)
            ->put(route('examenes.update', $examen), [
                'precio_sin_nota' => 100.00,
                'precio_con_nota' => 200.00,
                'precios_clinicas' => [
                    $clinicaA->id => [
                        'sin_nota' => '150.00',
                        'con_nota' => '250.00',
                    ],
                    $clinicaB->id => [
                        'sin_nota' => '120.00',
                        'con_nota' => '220.00',
                    ],
                ],
            ]);

        // THEN redirects to index
        $response->assertRedirect(route('examenes.index'));

        // THEN pivot rows exist with correct values
        $this->assertDatabaseHas('clinica_examen', [
            'clinica_id' => $clinicaA->id,
            'examen_id' => $examen->id,
            'precio_sin_nota' => 150.00,
            'precio_con_nota' => 250.00,
        ]);
        $this->assertDatabaseHas('clinica_examen', [
            'clinica_id' => $clinicaB->id,
            'examen_id' => $examen->id,
            'precio_sin_nota' => 120.00,
            'precio_con_nota' => 220.00,
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // REQ-PRICE-005: Empty inputs store NULL in pivot
    // ────────────────────────────────────────────────────────────

    public function test_guardar_precios_vacios_guarda_null_en_pivot(): void
    {
        // GIVEN 1 clinic and 1 examen exist
        $clinica = Clinica::factory()->create();
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 200.00,
        ]);

        // WHEN saving with empty/blank per-clinic prices
        $response = $this->actingAs($this->admin)
            ->put(route('examenes.update', $examen), [
                'precio_sin_nota' => 100.00,
                'precio_con_nota' => 200.00,
                'precios_clinicas' => [
                    $clinica->id => [
                        'sin_nota' => '',
                        'con_nota' => '',
                    ],
                ],
            ]);

        // THEN redirects
        $response->assertRedirect(route('examenes.index'));

        // THEN pivot stores NULL for both columns
        $this->assertDatabaseHas('clinica_examen', [
            'clinica_id' => $clinica->id,
            'examen_id' => $examen->id,
            'precio_sin_nota' => null,
            'precio_con_nota' => null,
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // REQ-PRICE-006: Index shows override badge only when > 0
    // ────────────────────────────────────────────────────────────

    public function test_indice_muestra_badge_cuando_hay_overrides(): void
    {
        // GIVEN Examen X has override for 1 clinic
        $clinica = Clinica::factory()->create();
        $examen = Examen::factory()->create([
            'nombre' => 'Examen Con Override',
        ]);
        $examen->clinicas()->attach($clinica->id, [
            'precio_sin_nota' => 150.00,
            'precio_con_nota' => null,
        ]);

        // WHEN viewing exam index
        $response = $this->actingAs($this->admin)
            ->get(route('examenes.index'));

        // THEN badge appears near exam name
        $response->assertStatus(200);
        $response->assertSee('Examen Con Override');
        $response->assertSee('1 clínica');
    }

    public function test_indice_no_muestra_badge_sin_overrides(): void
    {
        // GIVEN Examen Y has zero overrides
        $examen = Examen::factory()->create([
            'nombre' => 'Examen Sin Override',
        ]);

        // WHEN viewing exam index
        $response = $this->actingAs($this->admin)
            ->get(route('examenes.index'));

        // THEN no badge appears
        $response->assertStatus(200);
        $response->assertSee('Examen Sin Override');
        $response->assertDontSee('clínica');
    }
}
