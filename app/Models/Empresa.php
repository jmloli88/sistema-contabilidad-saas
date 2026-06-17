<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Empresa extends Model
{
    use HasFactory;

    protected $fillable = ['nombre'];

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
     * Check if any user in this empresa has an active subscription.
     */
    public function hasActiveSubscription(): bool
    {
        return $this->users()->whereHas('subscriptions', function ($q) {
            $q->where('stripe_status', 'active')
              ->where('ends_at', '>', now());
        })->exists();
    }

    /**
     * Get the active subscription for this empresa (first found).
     */
    public function activeSubscription()
    {
        return $this->users()
            ->whereHas('subscriptions', function ($q) {
                $q->where('stripe_status', 'active')->where('ends_at', '>', now());
            })
            ->with(['subscriptions' => function ($q) {
                $q->where('stripe_status', 'active')->where('ends_at', '>', now())->orderBy('ends_at', 'desc');
            }])
            ->first()?->subscription('default');
    }
}
