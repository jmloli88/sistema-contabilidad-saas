<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Empresa;
use App\Models\Clinica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UiRedesignPhase2Test extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empresa = Empresa::factory()->create(['nombre' => 'UI Test Empresa']);
        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'role' => 'administrador',
        ]);
        $this->empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_ui_redesign_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
    }

    // ──────────────────────────────────────────────
    // Task 2.1: Dashboard layout — root dashboard.blade.php
    // ──────────────────────────────────────────────
    public function test_dashboard_blade_shows_welcome_banner(): void
    {
        $response = $this->actingAs($this->admin)->get(route('dashboard'));

        $response->assertStatus(200);
        // The main dashboard (dashboard/index.blade.php) must still render
        $response->assertSee('Dashboard Financiero');
    }

    public function test_root_dashboard_blade_contains_quick_actions(): void
    {
        // The root dashboard.blade.php is a standalone layout not route-backed.
        // We verify it gets updated by checking the file content.
        // This is the RED phase — the old template does NOT contain "Nuevo Repase" button text.
        $path = resource_path('views/dashboard.blade.php');
        $content = file_get_contents($path);
        $this->assertStringContainsString('Nuevo Repase', $content,
            'Root dashboard.blade.php must contain quick action buttons after Phase 2 redesign.'
        );
    }

    // ──────────────────────────────────────────────
    // Task 2.2: Examenes Index
    // ──────────────────────────────────────────────
    public function test_examenes_index_renders_with_create_button(): void
    {
        $response = $this->actingAs($this->admin)->get(route('examenes.index'));

        $response->assertStatus(200);
        $response->assertSee('Nuevo Examen');
        $response->assertSee('Gestión de Precios de Exámenes');
    }

    // ──────────────────────────────────────────────
    // Task 2.3: Clinicas Index
    // ──────────────────────────────────────────────
    public function test_clinicas_index_renders_for_admin(): void
    {
        $response = $this->actingAs($this->admin)->get(route('clinicas.index'));

        $response->assertStatus(200);
        $response->assertSee('Clínicas');
        $response->assertSee('Nueva Clínica');
    }

    public function test_clinicas_index_shows_clinicas_in_table(): void
    {
        $clinica = Clinica::factory()->create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Clínica Visible',
            'direccion' => 'Calle Test 123',
            'telefono' => '555-1234',
        ]);

        $response = $this->actingAs($this->admin)->get(route('clinicas.index'));

        $response->assertStatus(200);
        $response->assertSee('Clínica Visible');
        $response->assertSee('Calle Test 123');
        $response->assertSee('555-1234');
    }

    // ──────────────────────────────────────────────
    // Task 2.4: Clinicas Create
    // ──────────────────────────────────────────────
    public function test_clinicas_create_renders_form(): void
    {
        $response = $this->actingAs($this->admin)->get(route('clinicas.create'));

        $response->assertStatus(200);
        $response->assertSee('Nueva Clínica');
        $response->assertSee('Nombre');
        $response->assertSee('Dirección');
        $response->assertSee('Teléfono');
        $response->assertSee('Guardar');
    }

    // ──────────────────────────────────────────────
    // Task 2.4: Clinicas Edit
    // ──────────────────────────────────────────────
    public function test_clinicas_edit_renders_form_with_data(): void
    {
        $clinica = Clinica::factory()->create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Clínica a Editar',
            'direccion' => 'Av. Principal 456',
            'telefono' => '555-5678',
        ]);

        $response = $this->actingAs($this->admin)->get(route('clinicas.edit', $clinica));

        $response->assertStatus(200);
        $response->assertSee('Editar Clínica');
        $response->assertSee('Clínica a Editar');
    }

    // ──────────────────────────────────────────────
    // Task 2.5: Global card pattern — views still render
    // after shadow-sm sm:rounded-lg replacement
    // ──────────────────────────────────────────────
    public function test_clinicas_show_renders_after_card_update(): void
    {
        $clinica = Clinica::factory()->create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Clínica para Show',
        ]);

        $response = $this->actingAs($this->admin)->get(route('clinicas.show', $clinica));

        $response->assertStatus(200);
        $response->assertSee('Clínica para Show');
    }
}
