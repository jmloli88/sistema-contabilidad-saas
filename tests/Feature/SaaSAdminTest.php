<?php

namespace Tests\Feature;

use App\Models\Empresa;
use App\Models\SaasAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaaSAdminTest extends TestCase
{
    use RefreshDatabase;

    // === SaaS Login Page Tests ===

    public function test_saas_login_page_can_be_rendered(): void
    {
        $response = $this->get('/saas/login');

        $response->assertStatus(200);
        $response->assertSee('Acceso Administradores SaaS');
    }

    public function test_saas_admin_can_login_via_saas_login(): void
    {
        $admin = SaasAdmin::factory()->create();

        $response = $this->post('/saas/login', [
            'email' => $admin->email,
            'password' => 'password',
        ]);

        $this->assertAuthenticatedAs($admin, 'saas');
        $response->assertRedirect(route('saas.admin.dashboard', absolute: false));
    }

    public function test_unknown_email_cannot_login_via_saas_login(): void
    {
        $response = $this->post('/saas/login', [
            'email' => 'nonexistent@example.com',
            'password' => 'password',
        ]);

        $this->assertGuest('saas');
        $response->assertSessionHasErrors('email');
    }

    // === Redirect Tests ===

    public function test_root_redirects_regular_user_to_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'usuario']);

        $response = $this->actingAs($user)->get('/');

        $response->assertRedirect(route('dashboard', absolute: false));
    }

    public function test_root_redirects_guest_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login', absolute: false));
    }

    // === Dashboard / Panel Access Tests ===

    public function test_saas_admin_dashboard_shows_kpis(): void
    {
        $admin = SaasAdmin::factory()->create();

        $response = $this->actingAs($admin, 'saas')->get('/saas/admin');

        $response->assertStatus(200);
        $response->assertSee('Dashboard SaaS');
        $response->assertSee('Total Empresas');
        $response->assertSee('Empresas Activas');
        $response->assertSee('Ingreso Mensual Estimado');
    }

    public function test_saas_admin_can_access_user_list(): void
    {
        $admin = SaasAdmin::factory()->create();

        $response = $this->actingAs($admin, 'saas')->get('/saas/admin/usuarios');

        $response->assertStatus(200);
        $response->assertSee('Panel de Administración SaaS');
    }

    public function test_user_list_shows_empresa_column(): void
    {
        $admin = SaasAdmin::factory()->create();
        $empresa = Empresa::factory()->create(['nombre' => 'Empresa TDD Test']);
        User::factory()->create([
            'empresa_id' => $empresa->id,
            'role' => 'usuario',
            'name' => 'Test User With Empresa',
        ]);

        $response = $this->actingAs($admin, 'saas')->get('/saas/admin/usuarios');

        $response->assertStatus(200);
        $response->assertSee('Empresa TDD Test');
    }

    public function test_dashboard_shows_empresas_kpi(): void
    {
        $admin = SaasAdmin::factory()->create();
        $empresa = Empresa::factory()->create();
        $empresaUser = User::factory()->create(['empresa_id' => $empresa->id, 'role' => 'administrador']);
        // Subscription is now on the empresa, not the user
        $empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_dashboard_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($admin, 'saas')->get('/saas/admin');

        $response->assertStatus(200);
        $response->assertSee('Empresas Activas');
    }

    public function test_guest_redirected_to_saas_login_on_admin_panel(): void
    {
        $response = $this->get('/saas/admin');

        $response->assertRedirect(route('saas.login'));
    }

    // === Subscription Management Tests ===

    public function test_saas_admin_can_extend_subscription_by_30_days(): void
    {
        $admin = SaasAdmin::factory()->create();
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['role' => 'usuario', 'empresa_id' => $empresa->id]);
        $user->empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_extend_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(5),
        ]);
        $originalEndsAt = $user->empresa->subscription('default')->ends_at->copy();

        $response = $this->actingAs($admin, 'saas')->post("/saas/admin/{$user->id}/extend");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->empresa->refresh();
        $this->assertEquals(
            $originalEndsAt->addDays(30)->format('Y-m-d'),
            $user->empresa->subscription('default')->ends_at->format('Y-m-d')
        );
        $this->assertEquals('active', $user->empresa->subscription('default')->stripe_status);
    }

    public function test_saas_admin_can_cancel_subscription(): void
    {
        $admin = SaasAdmin::factory()->create();
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['role' => 'usuario', 'empresa_id' => $empresa->id]);
        $user->empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_cancel_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(5),
        ]);

        $response = $this->actingAs($admin, 'saas')->post("/saas/admin/{$user->id}/cancel");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->empresa->refresh();
        $this->assertTrue($user->empresa->subscription('default')->ends_at->isToday());
        $this->assertEquals('canceled', $user->empresa->subscription('default')->stripe_status);
    }

    public function test_extend_creates_subscription_when_user_has_none(): void
    {
        $admin = SaasAdmin::factory()->create();
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['role' => 'usuario', 'empresa_id' => $empresa->id]);

        $this->assertNull($user->empresa->subscription('default'));

        $response = $this->actingAs($admin, 'saas')->post("/saas/admin/{$user->id}/extend");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $user->empresa->refresh();
        $this->assertNotNull($user->empresa->subscription('default'));
        $this->assertEquals('active', $user->empresa->subscription('default')->stripe_status);
        $this->assertTrue($user->empresa->subscription('default')->ends_at->isFuture());
    }

    public function test_history_page_shows_subscription_information(): void
    {
        $admin = SaasAdmin::factory()->create();
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['role' => 'usuario', 'name' => 'Test History User', 'empresa_id' => $empresa->id]);
        $user->empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_history_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($admin, 'saas')->get("/saas/admin/{$user->id}/history");

        $response->assertStatus(200);
        $response->assertSee('Test History User');
        $response->assertSee('Activo');
        $response->assertSee('sub_history_test');
    }

    // === Warning Banner Tests (system users, not SaaS) ===

    public function test_warning_banner_visible_when_subscription_ending_within_7_days(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['role' => 'usuario', 'empresa_id' => $empresa->id]);
        $user->empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_banner_soon',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(5),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('vence en');
        $response->assertSee('Renovar ahora');
    }

    public function test_warning_banner_hidden_when_subscription_active_with_30_days(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['role' => 'usuario', 'empresa_id' => $empresa->id]);
        $user->empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_banner_active',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertDontSee('vence en');
    }

    // === Schedule Command Test ===

    public function test_schedule_command_runs_without_errors(): void
    {
        $this->artisan('subscription:warn-expiring')
            ->assertExitCode(0);
    }
}
