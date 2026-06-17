<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Model for prediction configuration parameters
 * 
 * @property int $id
 * @property string $key
 * @property string $value
 * @property string|null $description
 * @property string|null $validation_rules
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class PredictionConfiguration extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     */
    protected $table = 'prediction_configurations';

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'key',
        'value',
        'description',
        'validation_rules',
    ];

    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get configuration by key
     */
    public static function getByKey(string $key): ?self
    {
        return static::where('key', $key)->first();
    }

    /**
     * Set configuration value by key
     */
    public static function setByKey(string $key, string $value, ?string $description = null, ?string $validationRules = null): self
    {
        return static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
                'validation_rules' => $validationRules,
            ]
        );
    }

    /**
     * Get parsed value based on type
     */
    public function getParsedValueAttribute()
    {
        // Try to decode as JSON first
        $decoded = json_decode($this->value, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // Check if it's numeric
        if (is_numeric($this->value)) {
            return (float) $this->value;
        }

        // Return as string
        return $this->value;
    }
}