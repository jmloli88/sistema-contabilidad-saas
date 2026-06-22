<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleCalendarEvent extends Model
{
    protected $fillable = [
        'agenda_id',
        'empresa_id',
        'google_event_id',
        'synced_at',
    ];

    protected $casts = [
        'synced_at' => 'datetime',
    ];

    public function agenda(): BelongsTo
    {
        return $this->belongsTo(Agenda::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
