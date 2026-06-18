<?php

namespace Tests\Feature;

use App\Models\Clinica;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionDualPathTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Scenario: Empresa with active subscription grants access.
     */
    public function test_empresa_subscription_resolves_access(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['empresa_id' => $empresa->id]);
        // Subscription on the empresa
        $empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_empresa_active',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
    }

    /**
     * Scenario: No subscription at all redirects to billing.
     */
    public function test_no_subscription_redirects_to_billing(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['empresa_id' => $empresa->id]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/billing');
    }

    /**
     * Scenario: Billing routes bypass subscription check.
     */
    public function test_billing_routes_are_not_blocked(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['empresa_id' => $empresa->id]);

        $response = $this->actingAs($user)->get('/billing');

        $response->assertStatus(200);
    }

    /**
     * Scenario: Expired empresa sub redirects to billing.
     */
    public function test_expired_empresa_with_no_clinic_fallback_redirects_to_billing(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['empresa_id' => $empresa->id]);
        // Expired subscription on the empresa
        $empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_expired_no_fallback',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertRedirect('/billing');
    }
}
