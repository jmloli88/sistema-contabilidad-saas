<?php

namespace Tests\Unit\Models;

use App\Models\Empresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    // === subscriptionEndingSoon ===

    public function test_subscription_ending_soon_returns_true_when_ends_at_is_within_days(): void
    {
        $user = User::factory()->create();
        $user->ends_at = now()->addDays(6);

        $this->assertTrue($user->subscriptionEndingSoon(7));
    }

    public function test_subscription_ending_soon_returns_false_when_ends_at_is_beyond_days(): void
    {
        $user = User::factory()->create();
        $user->ends_at = now()->addDays(8);

        $this->assertFalse($user->subscriptionEndingSoon(7));
    }

    public function test_subscription_ending_soon_returns_false_when_ends_at_is_null(): void
    {
        $user = User::factory()->create();
        $user->ends_at = null;

        $this->assertFalse($user->subscriptionEndingSoon(7));
    }

    public function test_subscription_ending_soon_returns_true_when_own_subscription_is_ending_soon(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['empresa_id' => $empresa->id]);
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_ending_soon',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(5),
        ]);

        $this->assertTrue($user->subscriptionEndingSoon(7));
    }

    public function test_subscription_ending_soon_returns_false_when_own_subscription_is_not_ending_soon(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['empresa_id' => $empresa->id]);
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_far_away',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);

        $this->assertFalse($user->subscriptionEndingSoon(7));
    }

    public function test_subscription_ending_soon_returns_false_when_no_subscription(): void
    {
        $empresa = Empresa::factory()->create();
        $user = User::factory()->create(['empresa_id' => $empresa->id]);

        $this->assertFalse($user->subscriptionEndingSoon(7));
    }
}
