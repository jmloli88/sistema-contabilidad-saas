<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Empresa;
use App\Models\Examen;
use App\Models\Clinica;
use App\Models\Repase;
use App\Models\RepaseExamen;

class ExamenCrudTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();
        $this->empresa = Empresa::factory()->create(['nombre' => 'CRUD Test Empresa']);
        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'role' => 'administrador',
        ]);
        $this->empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_crud_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
    }

    // ──────────────────────────────────────────────
    // REQ-CRUD-001: Create exam via controller
    // ──────────────────────────────────────────────

    public function test_admin_can_view_create_exam_form(): void
    {
        // WHEN admin visits the create exam page
        $response = $this->actingAs($this->admin)
            ->get(route('examenes.create'));

        // THEN the form is rendered
        $response->assertStatus(200);
        $response->assertViewIs('examenes.create');
    }

    public function test_admin_can_store_new_exam(): void
    {
        // WHEN admin submits valid exam data
        $response = $this->actingAs($this->admin)
            ->post(route('examenes.store'), [
                'nombre' => 'Nuevo Examen Test',
                'precio_sin_nota' => 100.00,
                'precio_con_nota' => 150.00,
            ]);

        // THEN exam is created and redirects to index
        $response->assertRedirect(route('examenes.index'));
        $this->assertDatabaseHas('examenes', [
            'nombre' => 'Nuevo Examen Test',
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 150.00,
            'empresa_id' => $this->empresa->id,
            'is_active' => true,
        ]);
    }

    public function test_store_exam_validates_nombre_required(): void
    {
        // WHEN submitting without nombre
        $response = $this->actingAs($this->admin)
            ->post(route('examenes.store'), [
                'nombre' => '',
                'precio_sin_nota' => 100.00,
                'precio_con_nota' => 150.00,
            ]);

        // THEN validation fails
        $response->assertSessionHasErrors('nombre');
    }

    public function test_store_exam_validates_precio_con_nota_greater(): void
    {
        // WHEN precio_con_nota is not greater than precio_sin_nota
        $response = $this->actingAs($this->admin)
            ->post(route('examenes.store'), [
                'nombre' => 'Test Precio',
                'precio_sin_nota' => 200.00,
                'precio_con_nota' => 100.00,
            ]);

        // THEN validation fails
        $response->assertSessionHasErrors('precio_con_nota');
    }

    // ──────────────────────────────────────────────
    // REQ-CRUD-002: Toggle exam active/inactive
    // ──────────────────────────────────────────────

    public function test_admin_can_toggle_exam_to_inactive(): void
    {
        // GIVEN an active exam
        $examen = Examen::factory()->create([
            'empresa_id' => $this->empresa->id,
            'is_active' => true,
        ]);

        // WHEN admin toggles it
        $response = $this->actingAs($this->admin)
            ->patch(route('examenes.toggle', $examen));

        // THEN exam becomes inactive
        $response->assertRedirect(route('examenes.index'));
        $this->assertDatabaseHas('examenes', [
            'id' => $examen->id,
            'is_active' => false,
        ]);
    }

    public function test_admin_can_toggle_exam_back_to_active(): void
    {
        // GIVEN an inactive exam
        $examen = Examen::factory()->create([
            'empresa_id' => $this->empresa->id,
            'is_active' => false,
        ]);

        // WHEN admin toggles it
        $response = $this->actingAs($this->admin)
            ->patch(route('examenes.toggle', $examen));

        // THEN exam becomes active
        $response->assertRedirect(route('examenes.index'));
        $this->assertDatabaseHas('examenes', [
            'id' => $examen->id,
            'is_active' => true,
        ]);
    }

    public function test_guest_cannot_toggle_exam(): void
    {
        // GIVEN an exam
        $examen = Examen::factory()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        // WHEN guest tries to toggle
        $response = $this->patch(route('examenes.toggle', $examen));

        // THEN redirects to login
        $response->assertRedirect(route('login'));
    }

    // ──────────────────────────────────────────────
    // REQ-CRUD-003: Delete exam (blocked when has history)
    // ──────────────────────────────────────────────

    public function test_admin_can_delete_exam_without_history(): void
    {
        // GIVEN an exam with no repase history
        $examen = Examen::factory()->create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Deletable Exam',
        ]);

        // WHEN admin deletes it
        $response = $this->actingAs($this->admin)
            ->delete(route('examenes.destroy', $examen));

        // THEN exam is permanently deleted
        $response->assertRedirect(route('examenes.index'));
        $this->assertDatabaseMissing('examenes', [
            'id' => $examen->id,
        ]);
    }

    public function test_cannot_delete_exam_with_repase_history(): void
    {
        // GIVEN an exam that has been used in a repase
        $examen = Examen::factory()->create([
            'empresa_id' => $this->empresa->id,
            'nombre' => 'Protected Exam',
        ]);
        $clinica = Clinica::factory()->create(['empresa_id' => $this->empresa->id]);
        $repase = Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => now()->format('Y-m-d'),
        ]);
        RepaseExamen::factory()->create([
            'repase_id' => $repase->id,
            'examen_id' => $examen->id,
            'cantidad' => 1,
            'precio_unitario_usado' => 100.00,
            'subtotal' => 100.00,
        ]);

        // WHEN admin tries to delete
        $response = $this->actingAs($this->admin)
            ->delete(route('examenes.destroy', $examen));

        // THEN deletion is blocked
        $response->assertRedirect(route('examenes.index'));
        $response->assertSessionHas('error');

        // AND exam still exists
        $this->assertDatabaseHas('examenes', [
            'id' => $examen->id,
        ]);
    }

    public function test_guest_cannot_delete_exam(): void
    {
        // GIVEN an exam
        $examen = Examen::factory()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        // WHEN guest tries to delete
        $response = $this->delete(route('examenes.destroy', $examen));

        // THEN redirects to login
        $response->assertRedirect(route('login'));
    }
}
