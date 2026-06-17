<?php

namespace Tests\Unit\Models;

use App\Models\Clinica;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserRoleTest extends TestCase
{
    use RefreshDatabase;

    // === subscriptionEndingSoon (backward compat, no clinica) ===

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

    // === hasActiveSubscriptionInClinic ===

    public function test_has_active_subscription_in_clinic_returns_true_when_clinic_member_has_active_sub(): void
    {
        $clinic = Clinica::factory()->create();
        $admin = User::factory()->create(['clinica_id' => $clinic->id, 'role' => 'administrador']);
        $admin->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_active_clinic',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
        $member = User::factory()->create(['clinica_id' => $clinic->id, 'role' => 'usuario']);

        $this->assertTrue($member->hasActiveSubscriptionInClinic());
    }

    public function test_has_active_subscription_in_clinic_returns_false_when_no_clinic_member_has_active_sub(): void
    {
        $clinic = Clinica::factory()->create();
        $admin = User::factory()->create(['clinica_id' => $clinic->id, 'role' => 'administrador']);
        $admin->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_expired_clinic',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->subDays(1),
        ]);
        $member = User::factory()->create(['clinica_id' => $clinic->id, 'role' => 'usuario']);

        $this->assertFalse($member->hasActiveSubscriptionInClinic());
    }

    public function test_has_active_subscription_in_clinic_returns_true_for_own_sub_when_no_clinica(): void
    {
        $user = User::factory()->create(['clinica_id' => null, 'role' => 'usuario']);
        $user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_own_active',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);

        $this->assertTrue($user->hasActiveSubscriptionInClinic());
    }

    public function test_has_active_subscription_in_clinic_returns_false_for_no_own_sub_when_no_clinica(): void
    {
        $user = User::factory()->create(['clinica_id' => null, 'role' => 'usuario']);

        $this->assertFalse($user->hasActiveSubscriptionInClinic());
    }

    // === subscriptionEndingSoon (clinic-aware) ===

    public function test_subscription_ending_soon_returns_true_when_clinic_subscription_is_ending_soon(): void
    {
        $clinic = Clinica::factory()->create();
        $admin = User::factory()->create(['clinica_id' => $clinic->id, 'role' => 'administrador']);
        $admin->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_ending_soon',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(5),
        ]);
        $member = User::factory()->create(['clinica_id' => $clinic->id, 'role' => 'usuario']);

        $this->assertTrue($member->subscriptionEndingSoon(7));
    }

    public function test_subscription_ending_soon_returns_false_when_clinic_subscription_is_not_ending_soon(): void
    {
        $clinic = Clinica::factory()->create();
        $admin = User::factory()->create(['clinica_id' => $clinic->id, 'role' => 'administrador']);
        $admin->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_far_away',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
        $member = User::factory()->create(['clinica_id' => $clinic->id, 'role' => 'usuario']);

        $this->assertFalse($member->subscriptionEndingSoon(7));
    }

    public function test_subscription_ending_soon_returns_false_when_clinic_has_no_active_subscription(): void
    {
        $clinic = Clinica::factory()->create();
        $member = User::factory()->create(['clinica_id' => $clinic->id, 'role' => 'usuario']);

        $this->assertFalse($member->subscriptionEndingSoon(7));
    }
}
