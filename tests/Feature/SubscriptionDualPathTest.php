<?php

namespace Tests\Feature;

use App\Models\Clinica;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
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
        $user->subscriptions()->create([
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
     * Scenario: User without empresa_id (legacy data) falls back to clinic-shared sub.
     */
    public function test_user_without_empresa_falls_back_to_clinic_shared(): void
    {
        $clinic = Clinica::factory()->create();

        // Another user in the clinic has active subscription (clinic-shared)
        $admin = User::factory()->create([
            'empresa_id' => null,
            'clinica_id' => $clinic->id,
        ]);
        $admin->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_clinic_shared',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);

        // Our user has no empresa_id (legacy data during transition)
        $user = User::factory()->create([
            'empresa_id' => null,
            'clinica_id' => $clinic->id,
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
     * Scenario: Log records empresa path when empresa sub resolves.
     */
    public function test_log_records_empresa_path(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['empresa_id' => $empresa->id]);
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_log_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(fn($message, $context) =>
                $message === 'Subscription check: empresa path used'
                && ($context['user_id'] ?? null) === $user->id
                && ($context['empresa_id'] ?? null) === $empresa->id
            );

        $this->actingAs($user)->get('/dashboard');
    }

    /**
     * Scenario: Log records fallback path when fallback resolves (legacy user without empresa_id).
     */
    public function test_log_records_fallback_path(): void
    {
        $clinic = Clinica::factory()->create();

        // Another user has active clinic-shared sub (legacy data)
        $admin = User::factory()->create([
            'empresa_id' => null,
            'clinica_id' => $clinic->id,
        ]);
        $admin->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_fallback_log',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);

        // User without empresa_id (legacy)
        $user = User::factory()->create([
            'empresa_id' => null,
            'clinica_id' => $clinic->id,
        ]);

        Log::shouldReceive('info')
            ->once()
            ->withArgs(fn($message, $context) =>
                $message === 'Subscription check: fallback path used'
                && ($context['user_id'] ?? null) === $user->id
                && ($context['path'] ?? null) === 'clinic-shared-fallback'
            );

        $this->actingAs($user)->get('/dashboard');
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
     * Scenario: Expired empresa sub with no fallback redirects to billing.
     */
    public function test_expired_empresa_with_no_clinic_fallback_redirects_to_billing(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['empresa_id' => $empresa->id]);
        $user->subscriptions()->create([
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
