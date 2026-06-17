<?php

namespace App\Services\Predictive;

use App\Contracts\PredictiveConfigInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Exception;

/**
 * Configuration management system for predictive analysis module
 * 
 * Handles parameter management, validation, overrides, and audit trail
 * for all predictive analysis configuration settings.
 */
class PredictiveConfig implements PredictiveConfigInterface
{
    /**
     * Cache key prefix for configuration values
     */
    private const CACHE_PREFIX = 'predictive_config_';
    
    /**
     * Cache duration for configuration values (in minutes)
     */
    private const CACHE_DURATION = 60;

    /**
     * Default configuration parameters with their acceptable ranges
     */
    private const DEFAULT_PARAMETERS = [
        'expense_alert_threshold' => [
            'default' => 25,
            'validation' => 'numeric|min:1|max:50',
            'description' => 'Umbral de alerta para gastos (% sobre promedio)',
            'type' => 'numeric'
        ],
        'active_algorithms' => [
            'default' => ['linear_regression', 'moving_average', 'seasonal'],
            'validation' => 'array|min:1',
            'description' => 'Algoritmos activos para predicción',
            'type' => 'array'
        ],
        'cache_duration_minutes' => [
            'default' => 60,
            'validation' => 'numeric|min:5|max:1440',
            'description' => 'Duración del caché en minutos',
            'type' => 'numeric'
        ],
        'min_historical_months' => [
            'default' => 12,
            'validation' => 'numeric|min:6|max:60',
            'description' => 'Mínimo de meses históricos requeridos',
            'type' => 'numeric'
        ],
        'capacity_alert_threshold' => [
            'default' => 85,
            'validation' => 'numeric|min:50|max:95',
            'description' => 'Umbral de alerta de capacidad (%)',
            'type' => 'numeric'
        ]
    ];

    /**
     * Get a configuration value by key
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed Configuration value
     */
    public function get(string $key, $default = null)
    {
        // Check cache first
        $cacheKey = self::CACHE_PREFIX . $key;
        $cachedValue = Cache::get($cacheKey);
        
        if ($cachedValue !== null) {
            return $this->parseValue($cachedValue, $key);
        }

        // Get from database
        $config = DB::table('prediction_configurations')
            ->where('key', $key)
            ->first();

        if (!$config) {
            // Return default value if available
            if (isset(self::DEFAULT_PARAMETERS[$key])) {
                return self::DEFAULT_PARAMETERS[$key]['default'];
            }
            return $default;
        }

        $value = $this->parseValue($config->value, $key);
        
        // Cache the value
        Cache::put($cacheKey, $config->value, self::CACHE_DURATION);
        
        return $value;
    }

    /**
     * Set a configuration value with validation
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @param int|null $userId User ID for audit trail (defaults to current user)
     * @return bool Success status
     * @throws InvalidArgumentException If validation fails
     */
    public function set(string $key, $value, ?int $userId = null): bool
    {
        // Validate the parameter
        $this->validateParameter($key, $value);

        // Get current value for audit trail
        $oldValue = $this->get($key);
        
        // Prepare value for storage
        $storedValue = $this->prepareValueForStorage($value, $key);
        
        try {
            DB::beginTransaction();

            // Update or insert configuration
            DB::table('prediction_configurations')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $storedValue,
                    'description' => self::DEFAULT_PARAMETERS[$key]['description'] ?? null,
                    'validation_rules' => self::DEFAULT_PARAMETERS[$key]['validation'] ?? null,
                    'updated_at' => now(),
                ]
            );

            // Create audit trail entry
            $this->createAuditEntry($key, $oldValue, $value, $userId);

            // Clear configuration cache
            Cache::forget(self::CACHE_PREFIX . $key);
            
            // Clear prediction cache when configuration changes
            $this->invalidatePredictionCache($key);

            DB::commit();
            
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw new InvalidArgumentException("Failed to set configuration: " . $e->getMessage());
        }
    }

    /**
     * Get all configuration values
     * 
     * @return array All configuration values
     */
    public function getAll(): array
    {
        $configs = DB::table('prediction_configurations')->get();
        $result = [];
        
        foreach ($configs as $config) {
            $result[$config->key] = $this->parseValue($config->value, $config->key);
        }
        
        // Add any missing default values
        foreach (self::DEFAULT_PARAMETERS as $key => $params) {
            if (!isset($result[$key])) {
                $result[$key] = $params['default'];
            }
        }
        
        return $result;
    }

    /**
     * Override a configuration value temporarily (session-based)
     * 
     * @param string $key Configuration key
     * @param mixed $value Override value
     * @return bool Success status
     */
    public function override(string $key, $value): bool
    {
        // Validate the parameter
        $this->validateParameter($key, $value);
        
        // Store in session for temporary override
        session()->put("config_override_{$key}", $value);
        
        return true;
    }

    /**
     * Get configuration value with override support
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value
     * @return mixed Configuration value (override takes precedence)
     */
    public function getWithOverride(string $key, $default = null)
    {
        // Check for session override first
        $overrideKey = "config_override_{$key}";
        if (session()->has($overrideKey)) {
            return session()->get($overrideKey);
        }
        
        return $this->get($key, $default);
    }

    /**
     * Clear all configuration overrides
     * 
     * @return void
     */
    public function clearOverrides(): void
    {
        $keys = array_keys(self::DEFAULT_PARAMETERS);
        foreach ($keys as $key) {
            session()->forget("config_override_{$key}");
        }
    }

    /**
     * Get configuration audit trail
     * 
     * @param string|null $key Specific configuration key (null for all)
     * @param int $limit Number of records to return
     * @return array Audit trail entries
     */
    public function getAuditTrail(?string $key = null, int $limit = 50): array
    {
        $query = DB::table('prediction_configuration_audit')
            ->orderBy('created_at', 'desc')
            ->limit($limit);
            
        if ($key) {
            $query->where('config_key', $key);
        }
        
        return $query->get()->toArray();
    }

    /**
     * Validate configuration parameter
     * 
     * @param string $key Configuration key
     * @param mixed $value Value to validate
     * @throws InvalidArgumentException If validation fails
     */
    private function validateParameter(string $key, $value): void
    {
        if (!isset(self::DEFAULT_PARAMETERS[$key])) {
            throw new InvalidArgumentException("Unknown configuration parameter: {$key}");
        }
        
        $rules = self::DEFAULT_PARAMETERS[$key]['validation'];
        $type = self::DEFAULT_PARAMETERS[$key]['type'];
        
        // Special handling for array types
        if ($type === 'array' && is_array($value)) {
            $validator = Validator::make(['value' => $value], ['value' => $rules]);
        } else {
            $validator = Validator::make(['value' => $value], ['value' => $rules]);
        }
        
        if ($validator->fails()) {
            throw new InvalidArgumentException(
                "Validation failed for {$key}: " . implode(', ', $validator->errors()->all())
            );
        }
    }

    /**
     * Parse stored value based on parameter type
     * 
     * @param string $storedValue Stored value from database
     * @param string $key Configuration key
     * @return mixed Parsed value
     */
    private function parseValue(string $storedValue, string $key)
    {
        if (!isset(self::DEFAULT_PARAMETERS[$key])) {
            return $storedValue;
        }
        
        $type = self::DEFAULT_PARAMETERS[$key]['type'];
        
        switch ($type) {
            case 'numeric':
                return is_numeric($storedValue) ? (float) $storedValue : $storedValue;
            case 'array':
                $decoded = json_decode($storedValue, true);
                return is_array($decoded) ? $decoded : [$storedValue];
            default:
                return $storedValue;
        }
    }

    /**
     * Prepare value for database storage
     * 
     * @param mixed $value Value to store
     * @param string $key Configuration key
     * @return string Prepared value
     */
    private function prepareValueForStorage($value, string $key): string
    {
        if (!isset(self::DEFAULT_PARAMETERS[$key])) {
            return (string) $value;
        }
        
        $type = self::DEFAULT_PARAMETERS[$key]['type'];
        
        switch ($type) {
            case 'array':
                return json_encode($value);
            default:
                return (string) $value;
        }
    }

    /**
     * Create audit trail entry
     * 
     * @param string $key Configuration key
     * @param mixed $oldValue Previous value
     * @param mixed $newValue New value
     * @param int|null $userId User ID
     */
    private function createAuditEntry(string $key, $oldValue, $newValue, ?int $userId): void
    {
        $userId = $userId ?? Auth::id();
        
        DB::table('prediction_configuration_audit')->insert([
            'config_key' => $key,
            'old_value' => $this->prepareValueForStorage($oldValue, $key),
            'new_value' => $this->prepareValueForStorage($newValue, $key),
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }

    /**
     * Get available configuration parameters
     * 
     * @return array Available parameters with metadata
     */
    public function getAvailableParameters(): array
    {
        return self::DEFAULT_PARAMETERS;
    }

    /**
     * Reset configuration to default values
     * 
     * @param string|null $key Specific key to reset (null for all)
     * @param int|null $userId User ID for audit trail
     * @return bool Success status
     */
    public function resetToDefaults(?string $key = null, ?int $userId = null): bool
    {
        try {
            DB::beginTransaction();
            
            if ($key) {
                // Reset specific parameter
                if (!isset(self::DEFAULT_PARAMETERS[$key])) {
                    throw new InvalidArgumentException("Unknown configuration parameter: {$key}");
                }
                
                $this->set($key, self::DEFAULT_PARAMETERS[$key]['default'], $userId);
            } else {
                // Reset all parameters
                foreach (self::DEFAULT_PARAMETERS as $paramKey => $params) {
                    $this->set($paramKey, $params['default'], $userId);
                }
            }
            
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw new InvalidArgumentException("Failed to reset configuration: " . $e->getMessage());
        }
    }

    /**
     * Clear configuration cache
     * 
     * @param string|null $key Specific key to clear (null for all)
     */
    public function clearCache(?string $key = null): void
    {
        if ($key) {
            Cache::forget(self::CACHE_PREFIX . $key);
        } else {
            foreach (array_keys(self::DEFAULT_PARAMETERS) as $paramKey) {
                Cache::forget(self::CACHE_PREFIX . $paramKey);
            }
        }
    }

    /**
     * Invalidate prediction cache when configuration changes
     * 
     * @param string $configKey Configuration key that changed
     */
    private function invalidatePredictionCache(string $configKey): void
    {
        try {
            // Use the CacheService for intelligent invalidation
            $cacheService = app(\App\Contracts\Predictive\CacheServiceInterface::class);
            $invalidatedCount = $cacheService->invalidateOnConfigChange($configKey);
            
            Log::channel('predictive')->info('Prediction cache invalidated due to config change', [
                'config_key' => $configKey,
                'invalidated_count' => $invalidatedCount
            ]);
            
        } catch (\Exception $e) {
            // Fallback to direct database clearing if CacheService fails
            Log::channel('predictive')->warning('CacheService unavailable, using fallback invalidation', [
                'config_key' => $configKey,
                'error' => $e->getMessage()
            ]);
            
            // Clear all prediction cache when configuration changes
            // This ensures predictions are recalculated with new parameters
            DB::table('prediction_cache')->delete();
            
            // Also clear any Laravel cache entries related to predictions
            $cacheKeys = [
                'income_predictions_*',
                'expense_forecasts_*',
                'capacity_analysis_*',
                'trend_analysis_*'
            ];
            
            foreach ($cacheKeys as $pattern) {
                // Note: Laravel doesn't have a native way to clear by pattern
                // In production, consider using Redis with pattern matching
                // For now, we'll rely on the database cache clearing above
            }
        }
    }
}