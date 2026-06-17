<?php

namespace App\Contracts\Predictive;

use App\DTOs\Predictive\PredictionResult;
use App\DTOs\Predictive\ExpenseForecast;
use App\DTOs\Predictive\CapacityAnalysis;
use App\DTOs\Predictive\SeasonalAnalysis;

/**
 * Interface for intelligent caching system for predictive analysis
 * 
 * Provides multi-level caching with prediction result caching,
 * cache key generation, invalidation logic, and fallback strategies.
 */
interface CacheServiceInterface
{
    /**
     * Cache a prediction result
     * 
     * @param string $predictionType Type of prediction (income, expense, capacity, trends)
     * @param array $filters Filters and parameters used for prediction
     * @param mixed $result Prediction result to cache
     * @param int|null $ttlMinutes Time to live in minutes (null for default)
     * @return bool Success status
     */
    public function cachePrediction(string $predictionType, array $filters, $result, ?int $ttlMinutes = null): bool;

    /**
     * Retrieve cached prediction result
     * 
     * @param string $predictionType Type of prediction
     * @param array $filters Filters and parameters
     * @return mixed|null Cached result or null if not found/expired
     */
    public function getCachedPrediction(string $predictionType, array $filters);

    /**
     * Generate unique cache key based on prediction type and filters
     * 
     * @param string $predictionType Type of prediction
     * @param array $filters Filters and parameters
     * @return string Unique cache key
     */
    public function generateCacheKey(string $predictionType, array $filters): string;

    /**
     * Invalidate cache for specific prediction type and filters
     * 
     * @param string|null $predictionType Specific type (null for all)
     * @param array|null $filters Specific filters (null for all)
     * @return int Number of cache entries invalidated
     */
    public function invalidateCache(?string $predictionType = null, ?array $filters = null): int;

    /**
     * Invalidate cache when configuration changes
     * 
     * @param string $configKey Configuration key that changed
     * @return int Number of cache entries invalidated
     */
    public function invalidateOnConfigChange(string $configKey): int;

    /**
     * Invalidate cache when new data is added
     * 
     * @param array $dataContext Context about the new data (table, date range, etc.)
     * @return int Number of cache entries invalidated
     */
    public function invalidateOnDataChange(array $dataContext): int;

    /**
     * Warm cache for frequently accessed predictions
     * 
     * @param array $predictionTypes Types to warm
     * @param array $commonFilters Common filter combinations
     * @return int Number of cache entries warmed
     */
    public function warmCache(array $predictionTypes, array $commonFilters = []): int;

    /**
     * Get cache statistics and monitoring data
     * 
     * @return array Cache statistics (hit rate, size, entries, etc.)
     */
    public function getCacheStatistics(): array;

    /**
     * Clean expired cache entries
     * 
     * @return int Number of entries cleaned
     */
    public function cleanExpiredEntries(): int;

    /**
     * Get fallback result when calculation fails
     * 
     * @param string $predictionType Type of prediction
     * @param array $filters Filters and parameters
     * @param \Exception $exception The exception that caused the failure
     * @return mixed|null Fallback result or null if no fallback available
     */
    public function getFallbackResult(string $predictionType, array $filters, \Exception $exception);

    /**
     * Check if cache is available and healthy
     * 
     * @return bool Cache health status
     */
    public function isHealthy(): bool;

    /**
     * Get cache configuration settings
     * 
     * @return array Current cache configuration
     */
    public function getConfiguration(): array;
}