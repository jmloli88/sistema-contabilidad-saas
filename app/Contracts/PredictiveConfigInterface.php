<?php

namespace App\Contracts;

/**
 * Interface for predictive configuration management
 */
interface PredictiveConfigInterface
{
    /**
     * Get a configuration value by key
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value if not found
     * @return mixed Configuration value
     */
    public function get(string $key, $default = null);

    /**
     * Set a configuration value with validation
     * 
     * @param string $key Configuration key
     * @param mixed $value Configuration value
     * @param int|null $userId User ID for audit trail
     * @return bool Success status
     */
    public function set(string $key, $value, ?int $userId = null): bool;

    /**
     * Get all configuration values
     * 
     * @return array All configuration values
     */
    public function getAll(): array;

    /**
     * Override a configuration value temporarily
     * 
     * @param string $key Configuration key
     * @param mixed $value Override value
     * @return bool Success status
     */
    public function override(string $key, $value): bool;

    /**
     * Get configuration value with override support
     * 
     * @param string $key Configuration key
     * @param mixed $default Default value
     * @return mixed Configuration value (override takes precedence)
     */
    public function getWithOverride(string $key, $default = null);

    /**
     * Clear all configuration overrides
     * 
     * @return void
     */
    public function clearOverrides(): void;

    /**
     * Get configuration audit trail
     * 
     * @param string|null $key Specific configuration key (null for all)
     * @param int $limit Number of records to return
     * @return array Audit trail entries
     */
    public function getAuditTrail(?string $key = null, int $limit = 50): array;

    /**
     * Get available configuration parameters
     * 
     * @return array Available parameters with metadata
     */
    public function getAvailableParameters(): array;

    /**
     * Reset configuration to default values
     * 
     * @param string|null $key Specific key to reset (null for all)
     * @param int|null $userId User ID for audit trail
     * @return bool Success status
     */
    public function resetToDefaults(?string $key = null, ?int $userId = null): bool;

    /**
     * Clear configuration cache
     * 
     * @param string|null $key Specific key to clear (null for all)
     */
    public function clearCache(?string $key = null): void;
}