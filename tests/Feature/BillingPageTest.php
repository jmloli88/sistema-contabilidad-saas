<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_subscription_shows_status_and_hides_payment_button(): void
    {
        $user = User::factory()->create(['role' => 'usuario']);
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_active',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(20),
        ]);

        $response = $this->actingAs($user)->get(route('billing.index'));

        $response->assertStatus(200);
        $response->assertSee('Activo');
        $response->assertDontSee('Pagar con PIX');
        $response->assertDontSee('Renovar');
    }

    public function test_expired_subscription_shows_expired_and_renovate_button(): void
    {
        $user = User::factory()->create(['role' => 'usuario']);
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_expired',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->subDays(1),
        ]);

        $response = $this->actingAs($user)->get(route('billing.index'));

        $response->assertStatus(200);
        $response->assertSee('Expirado');
        $response->assertSee('Renovar');
    }

    public function test_no_subscription_shows_no_subscription_message_and_payment_button(): void
    {
        $user = User::factory()->create(['role' => 'usuario']);

        $response = $this->actingAs($user)->get(route('billing.index'));

        $response->assertStatus(200);
        $response->assertSee('Sin suscripción activa');
        $response->assertSee('Pagar con PIX');
    }

    public function test_subscription_ending_soon_shows_remaining_days_and_payment_button(): void
    {
        $user = User::factory()->create(['role' => 'usuario']);
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_ending',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(5),
        ]);

        $response = $this->actingAs($user)->get(route('billing.index'));

        $response->assertStatus(200);
        $response->assertSee('Vence en');
        $response->assertSee('Pagar con PIX');
    }
}
