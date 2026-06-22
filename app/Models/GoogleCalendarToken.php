<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleCalendarToken extends Model
{
    protected $fillable = [
        'empresa_id',
        'access_token',
        'refresh_token',
        'expires_at',
        'google_email',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function isExpired(): bool
    {
        return !$this->expires_at || $this->expires_at->isPast();
    }
}
