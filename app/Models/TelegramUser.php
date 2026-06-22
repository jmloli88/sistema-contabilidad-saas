<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelegramUser extends Model
{
    protected $fillable = [
        'user_id',
        'chat_id',
        'username',
        'first_name',
        'last_name',
        'is_authenticated',
        'auth_token',
    ];

    protected $casts = [
        'is_authenticated' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
