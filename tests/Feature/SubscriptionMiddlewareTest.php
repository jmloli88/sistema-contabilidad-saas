<?php

namespace Tests\Feature;

use App\Models\Clinica;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    // === Backward compat tests (no clinica_id) ===

    public function test_expired_user_is_redirected_to_billing(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['role' => 'usuario', 'empresa_id' => $empresa->id]);
        $user->empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_expired',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/billing');
    }

    public function test_active_user_passes_through(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['role' => 'usuario', 'empresa_id' => $empresa->id]);
        $user->empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_active',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_expired_user_can_access_billing_page(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['role' => 'usuario', 'empresa_id' => $empresa->id]);
        $user->empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_billing',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($user)->get('/billing');

        $response->assertStatus(200);
        $response->assertSee('Suscripción');
    }

    public function test_expired_user_can_access_profile_page(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['role' => 'usuario', 'empresa_id' => $empresa->id]);
        $user->empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_profile',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_passes_through_to_auth_middleware(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_expired_user_without_subscription_row_is_redirected(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['role' => 'usuario', 'empresa_id' => $empresa->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/billing');
    }

    // === Clinic-aware subscription tests ===

    public function test_user_in_clinic_with_active_subscription_passes_through(): void
    {
        $empresa = Empresa::factory()->create();
        $clinic = Clinica::factory()->create(['empresa_id' => $empresa->id]);
        $admin = User::factory()->create([
            'empresa_id' => $empresa->id,
            'clinica_id' => $clinic->id,
            'role' => 'administrador',
        ]);
        // Subscription is on the empresa
        $empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_clinic_active',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
        $member = User::factory()->create([
            'empresa_id' => $empresa->id,
            'clinica_id' => $clinic->id,
            'role' => 'usuario',
        ]);

        $response = $this->actingAs($member)->get('/dashboard');

        $response->assertStatus(200);
    }

    public function test_user_in_clinic_without_active_subscription_is_redirected(): void
    {
        $empresa = Empresa::factory()->create();
        $clinic = Clinica::factory()->create(['empresa_id' => $empresa->id]);
        $admin = User::factory()->create([
            'empresa_id' => $empresa->id,
            'clinica_id' => $clinic->id,
            'role' => 'administrador',
        ]);
        // Expired subscription on the empresa
        $empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_clinic_expired',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->subDays(1),
        ]);
        $member = User::factory()->create([
            'empresa_id' => $empresa->id,
            'clinica_id' => $clinic->id,
            'role' => 'usuario',
        ]);

        $response = $this->actingAs($member)->get('/dashboard');

        $response->assertRedirect('/billing');
    }

    public function test_user_with_empresa_but_no_subscription_is_redirected(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['empresa_id' => $empresa->id, 'clinica_id' => null, 'role' => 'usuario']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/billing');
    }
}
