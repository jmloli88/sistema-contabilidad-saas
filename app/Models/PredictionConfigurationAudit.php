<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Model for prediction configuration audit trail
 * 
 * @property int $id
 * @property string $config_key
 * @property string|null $old_value
 * @property string $new_value
 * @property int|null $user_id
 * @property \Illuminate\Support\Carbon $created_at
 */
class PredictionConfigurationAudit extends Model
{
    /**
     * The table associated with the model.
     */
    protected $table = 'prediction_configuration_audit';

    /**
     * Indicates if the model should be timestamped.
     */
    public $timestamps = false;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'config_key',
        'old_value',
        'new_value',
        'user_id',
        'created_at',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    /**
     * Get the user who made the configuration change
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get parsed old value
     */
    public function getParsedOldValueAttribute()
    {
        if ($this->old_value === null) {
            return null;
        }

        return $this->parseValue($this->old_value);
    }

    /**
     * Get parsed new value
     */
    public function getParsedNewValueAttribute()
    {
        return $this->parseValue($this->new_value);
    }

    /**
     * Parse value from JSON or return as-is
     */
    private function parseValue(string $value)
    {
        $decoded = json_decode($value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return $value;
    }

    /**
     * Scope to get audit trail for specific configuration key
     */
    public function scopeForKey($query, string $key)
    {
        return $query->where('config_key', $key);
    }

    /**
     * Scope to get recent audit entries
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->orderBy('created_at', 'desc')->limit($limit);
    }
}