<?php

namespace Tests\Feature;

use App\Models\Clinica;
use App\Models\Empresa;
use App\Models\SaasAdmin;
use App\Models\User;
use App\Support\EmpresaContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaasEmpresaAdminTest extends TestCase
{
    use RefreshDatabase;

    private SaasAdmin $saasAdmin;

    protected function setUp(): void
    {
        parent::setUp();
        EmpresaContext::clear();

        $this->saasAdmin = SaasAdmin::factory()->create([
            'name' => 'Admin SaaS',
            'email' => 'saas@admin.com',
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // 4.1 [RED] Test: SaaS admin lists all empresas at /saas/admin/empresas
    // ────────────────────────────────────────────────────────────

    public function test_saas_admin_can_list_all_empresas(): void
    {
        $empresaA = Empresa::factory()->create(['nombre' => 'Empresa Alpha']);
        $empresaB = Empresa::factory()->create(['nombre' => 'Empresa Beta']);
        $empresaC = Empresa::factory()->create(['nombre' => 'Empresa Gamma']);

        $response = $this->actingAs($this->saasAdmin, 'saas')
            ->get('/saas/admin/empresas');

        $response->assertStatus(200);
        $response->assertSee('Empresa Alpha');
        $response->assertSee('Empresa Beta');
        $response->assertSee('Empresa Gamma');
    }

    public function test_empresa_list_shows_empresa_counts(): void
    {
        $empresa = Empresa::factory()->create(['nombre' => 'Empresa Count Test']);
        $user = User::factory()->create(['empresa_id' => $empresa->id]);
        $clinica = Clinica::factory()->create(['empresa_id' => $empresa->id]);

        $response = $this->actingAs($this->saasAdmin, 'saas')
            ->get('/saas/admin/empresas');

        $response->assertStatus(200);
        $response->assertSee('Empresa Count Test');
    }

    // ────────────────────────────────────────────────────────────
    // 4.2 [RED] Test: create empresa validates unique nombre
    // ────────────────────────────────────────────────────────────

    public function test_create_empresa_validates_unique_nombre(): void
    {
        Empresa::factory()->create(['nombre' => 'Nombre Existente']);

        $response = $this->actingAs($this->saasAdmin, 'saas')
            ->from('/saas/admin/empresas/create')
            ->post('/saas/admin/empresas', [
                'nombre' => 'Nombre Existente',
            ]);

        $response->assertSessionHasErrors('nombre');
        $response->assertRedirect('/saas/admin/empresas/create');
    }

    public function test_create_empresa_successfully(): void
    {
        $response = $this->actingAs($this->saasAdmin, 'saas')
            ->from('/saas/admin/empresas/create')
            ->post('/saas/admin/empresas', [
                'nombre' => 'Nueva Empresa SAS',
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('saas.admin.empresas.index'));

        $this->assertDatabaseHas('empresas', [
            'nombre' => 'Nueva Empresa SAS',
        ]);
    }

    // ────────────────────────────────────────────────────────────
    // 4.3 [RED] Test: detail dashboard shows users, clinicas, sub status
    // ────────────────────────────────────────────────────────────

    public function test_empresa_show_displays_users_and_clinicas_and_subscription_status(): void
    {
        $empresa = Empresa::factory()->create(['nombre' => 'Detalle Test']);
        $clinica = Clinica::factory()->create([
            'empresa_id' => $empresa->id,
            'nombre' => 'Clínica Detalle',
        ]);
        $user = User::factory()->create([
            'empresa_id' => $empresa->id,
            'clinica_id' => $clinica->id,
            'name' => 'User Detalle',
        ]);
        $empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_detalle_active',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->saasAdmin, 'saas')
            ->get("/saas/admin/empresas/{$empresa->id}");

        $response->assertStatus(200);
        $response->assertSee('Detalle Test');
        $response->assertSee('1');
        $response->assertSee('User Detalle');
    }

    public function test_empresa_show_shows_active_subscription_status(): void
    {
        $empresa = Empresa::factory()->create(['nombre' => 'Sub Active Test']);
        $user = User::factory()->create(['empresa_id' => $empresa->id]);
        $empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_active',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($this->saasAdmin, 'saas')
            ->get("/saas/admin/empresas/{$empresa->id}");

        $response->assertStatus(200);
    }

    public function test_empresa_show_shows_no_subscription_when_none_exists(): void
    {
        $empresa = Empresa::factory()->create(['nombre' => 'No Sub Test']);
        User::factory()->create(['empresa_id' => $empresa->id]);

        $response = $this->actingAs($this->saasAdmin, 'saas')
            ->get("/saas/admin/empresas/{$empresa->id}");

        $response->assertStatus(200);
    }

    // ────────────────────────────────────────────────────────────
    // 4.4 [RED] Test: user list filterable by empresa dropdown
    // ────────────────────────────────────────────────────────────

    public function test_user_list_filters_by_empresa(): void
    {
        $empresaA = Empresa::factory()->create(['nombre' => 'Filtro A']);
        $empresaB = Empresa::factory()->create(['nombre' => 'Filtro B']);

        $userA1 = User::factory()->create([
            'empresa_id' => $empresaA->id,
            'name' => 'Usuario A1',
        ]);
        $userA2 = User::factory()->create([
            'empresa_id' => $empresaA->id,
            'name' => 'Usuario A2',
        ]);
        User::factory()->create([
            'empresa_id' => $empresaB->id,
            'name' => 'Usuario B1',
        ]);

        $response = $this->actingAs($this->saasAdmin, 'saas')
            ->get('/saas/admin/usuarios?empresa_id=' . $empresaA->id);

        $response->assertStatus(200);
        $response->assertSee('Usuario A1');
        $response->assertSee('Usuario A2');
        $response->assertDontSee('Usuario B1');
    }

    public function test_user_list_shows_all_users_by_default(): void
    {
        $empresaA = Empresa::factory()->create(['nombre' => 'Default A']);
        $empresaB = Empresa::factory()->create(['nombre' => 'Default B']);

        User::factory()->create([
            'empresa_id' => $empresaA->id,
            'name' => 'Default User A',
        ]);
        User::factory()->create([
            'empresa_id' => $empresaB->id,
            'name' => 'Default User B',
        ]);

        $response = $this->actingAs($this->saasAdmin, 'saas')
            ->get('/saas/admin/usuarios');

        $response->assertStatus(200);
        $response->assertSee('Default User A');
        $response->assertSee('Default User B');
    }

    public function test_user_list_shows_empresa_filter_dropdown(): void
    {
        Empresa::factory()->create(['nombre' => 'Dropdown Empresa']);

        $response = $this->actingAs($this->saasAdmin, 'saas')
            ->get('/saas/admin/usuarios');

        $response->assertStatus(200);
        $response->assertSee('Dropdown Empresa');
        $response->assertSee('Todas las empresas');
    }
}
