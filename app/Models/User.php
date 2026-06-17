<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Models\Traits\ScopedByEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Cashier\Billable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, Billable, ScopedByEmpresa;

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
        'empresa_id',
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
     * Determina si la suscripción del usuario (a nivel de empresa) está próxima a vencer.
     *
     * @param  int  $days
     * @return bool
     */
    public function subscriptionEndingSoon(int $days): bool
    {
        // Check own subscription (empresa-level — legacy clinic-shared fallback removed in Phase 5)
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
