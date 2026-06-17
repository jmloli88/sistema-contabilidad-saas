<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Billable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'clinica_id',
    ];
    
    /**
     * Verifica si el usuario es administrador
     * 
     * @return bool
     */
    public function isAdmin(): bool
    {
        return $this->role === 'administrador';
    }
    
    /**
     * Verifica si el usuario es usuario regular
     * 
     * @return bool
     */
    public function isUser(): bool
    {
        return $this->role === 'usuario';
    }

    /**
     * Relación con la clínica a la que pertenece el usuario.
     *
     * @return BelongsTo
     */
    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    /**
     * Determina si el usuario (o su clínica) tiene una suscripción activa.
     *
     * Los usuarios sin clínica verifican su propia suscripción (backward compat).
     * Los usuarios con clínica verifican si ALGÚN miembro de la clínica tiene una suscripción activa.
     *
     * @return bool
     */
    public function hasActiveSubscriptionInClinic(): bool
    {
        if (!$this->clinica_id) {
            // Users without a clinic: check their own subscription (backward compat)
            $sub = $this->subscription('default');

            return $sub && $sub->ends_at && $sub->ends_at->isFuture();
        }

        return static::where('clinica_id', $this->clinica_id)
            ->whereHas('subscriptions', function ($q) {
                $q->where('stripe_status', 'active')
                  ->where('ends_at', '>', now());
            })
            ->exists();
    }

    /**
     * Determina si la suscripción del usuario (o de su clínica) está próxima a vencer.
     *
     * @param  int  $days
     * @return bool
     */
    public function subscriptionEndingSoon(int $days): bool
    {
        if ($this->clinica_id) {
            // Find ANY subscription in the clinic that's ending within the given days
            return static::where('clinica_id', $this->clinica_id)
                ->whereHas('subscriptions', function ($q) use ($days) {
                    $q->where('ends_at', '>', now()->startOfDay())
                      ->where('ends_at', '<=', now()->startOfDay()->addDays($days));
                })
                ->exists();
        }

        // No clinic: check own subscription (backward compat)
        $endsAt = $this->subscription('default')?->ends_at ?? $this->ends_at;

        if ($endsAt === null) {
            return false;
        }

        return $endsAt->endOfDay()->isFuture() && $endsAt->startOfDay()->lte(now()->startOfDay()->addDays($days));
    }

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
