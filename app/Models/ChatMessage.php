<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    protected $fillable = ['user_id', 'empresa_id', 'role', 'content', 'tokens_used', 'session_id'];

    protected $casts = [
        'tokens_used' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get a unique session ID for the current user.
     * Sessions expire after 1 hour of inactivity.
     */
    public static function getSessionId(int $userId): string
    {
        $lastMessage = static::where('user_id', $userId)
            ->latest()
            ->first();

        if ($lastMessage && $lastMessage->created_at->diffInMinutes(now()) < 60) {
            return $lastMessage->session_id;
        }

        return 'sess_' . $userId . '_' . now()->timestamp;
    }
}
