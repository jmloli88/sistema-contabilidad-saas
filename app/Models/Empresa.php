<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Cashier\Billable;

class Empresa extends Model
{
    use HasFactory, Billable;

    protected $fillable = ['nombre'];

    /**
     * Override Cashier's polymorphic subscriptions() with our empresa_id FK.
     */
    public function subscriptions(): HasMany
    {
        return $this->hasMany(\Laravel\Cashier\Subscription::class, 'empresa_id');
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function clinicas(): HasMany
    {
        return $this->hasMany(Clinica::class);
    }

    public function examenes(): HasMany
    {
        return $this->hasMany(Examen::class);
    }

    /**
     * Check if the empresa has an active subscription (any tier).
     */
    public function hasActiveSubscription(): bool
    {
        $sub = $this->activeSubscription();

        if (! $sub) {
            return false;
        }

        return $sub->ends_at && $sub->ends_at->startOfDay()->isFuture() && $sub->stripe_status === 'active';
    }

    /**
     * Check if the empresa has an active PREMIUM subscription.
     */
    public function hasPremium(): bool
    {
        $sub = $this->subscription('premium');

        if (! $sub) {
            return false;
        }

        return $sub->ends_at && $sub->ends_at->startOfDay()->isFuture() && $sub->stripe_status === 'active';
    }

    /**
     * Get the active subscription type for this empresa ('standard', 'premium', or null).
     */
    public function activeSubscriptionType(): ?string
    {
        if ($this->hasPremium()) {
            return 'premium';
        }

        $sub = $this->subscription('standard');

        if ($sub && $sub->ends_at && $sub->ends_at->startOfDay()->isFuture() && $sub->stripe_status === 'active') {
            return 'standard';
        }

        return null;
    }

    /**
     * Get the active subscription for this empresa (shortcut).
     */
    public function activeSubscription()
    {
        return $this->subscription('premium') ?? $this->subscription('standard');
    }
}
