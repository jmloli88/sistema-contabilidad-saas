<?php

namespace App\Services\Predictive;

use App\Contracts\Predictive\CacheServiceInterface;
use App\Contracts\PredictiveConfigInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

/**
 * Intelligent caching system for predictive analysis
 * 
 * Implements multi-level caching with prediction result caching,
 * intelligent cache key generation, targeted invalidation logic,
 * and fallback strategies for failed calculations.
 */
class CacheService implements CacheServiceInterface
{
    /**
     * Cache key prefix for memory cache
     */
    private const MEMORY_CACHE_PREFIX = 'predictive_memory_';
    
    /**
     * Cache statistics key
     */
    private const STATS_CACHE_KEY = 'predictive_cache_stats';
    
    /**
     * Supported prediction types
     */
    private const PREDICTION_TYPES = ['income', 'expense', 'capacity', 'trends'];
    
    /**
     * Configuration keys that affect different prediction types
     */
    private const CONFIG_PREDICTION_MAP = [
        'expense_alert_threshold' => ['expense'],
        'active_algorithms' => ['income', 'trends'],
        'min_historical_months' => ['income', 'expense', 'capacity', 'trends'],
        'capacity_alert_threshold' => ['capacity'],
        'cache_duration_minutes' => ['income', 'expense', 'capacity', 'trends']
    ];

    public function __construct(
        private PredictiveConfigInterface $config
    ) {}

    public function cachePrediction(string $predictionType, array $filters, $result, ?int $ttlMinutes = null): bool
    {
        try {
            // Validate prediction type
            if (!in_array($predictionType, self::PREDICTION_TYPES)) {
                Log::channel('predictive')->warning('Invalid prediction type for caching', [
                    'type' => $predictionType
                ]);
                return false;
            }

            // Generate cache key and filters hash
            $cacheKey = $this->generateCacheKey($predictionType, $filters);
            $filtersHash = $this->generateFiltersHash($filters);
            
            // Get TTL from configuration if not specified
            $ttl = $ttlMinutes ?? $this->config->getWithOverride('cache_duration_minutes', 60);
            $expiresAt = Carbon::now()->addMinutes($ttl);
            
            // Serialize result for storage
            $serializedResult = $this->serializeResult($result);
            
            // Calculate accuracy metrics if available
            $accuracyMetrics = $this->extractAccuracyMetrics($result);
            
            DB::beginTransaction();
            
            // Store in database cache
            DB::table('prediction_cache')->updateOrInsert(
                ['cache_key' => $cacheKey],
                [
                    'prediction_type' => $predictionType,
                    'filters_hash' => $filtersHash,
                    'result_data' => $serializedResult,
                    'accuracy_metrics' => $accuracyMetrics ? json_encode($accuracyMetrics) : null,
                    'expires_at' => $expiresAt,
                    'updated_at' => now(),
                ]
            );
            
            // Store in memory cache for faster access
            Cache::put(
                self::MEMORY_CACHE_PREFIX . $cacheKey,
                $result,
                $ttl * 60 // Convert to seconds
            );
            
            DB::commit();
            
            // Update cache statistics
            $this->updateCacheStatistics('cache_write', $predictionType);
            
            Log::channel('predictive')->info('Prediction cached successfully', [
                'type' => $predictionType,
                'cache_key' => $cacheKey,
                'ttl_minutes' => $ttl
            ]);
            
            return true;
            
        } catch (Exception $e) {
            DB::rollBack();
            
            Log::channel('predictive')->error('Failed to cache prediction', [
                'type' => $predictionType,
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            
            return false;
        }
    }

    public function getCachedPrediction(string $predictionType, array $filters)
    {
        try {
            $cacheKey = $this->generateCacheKey($predictionType, $filters);
            
            // Try memory cache first (fastest)
            $memoryCacheKey = self::MEMORY_CACHE_PREFIX . $cacheKey;
            $memoryResult = Cache::get($memoryCacheKey);
            
            if ($memoryResult !== null) {
                $this->updateCacheStatistics('memory_hit', $predictionType);
                
                Log::channel('predictive')->debug('Cache hit (memory)', [
                    'type' => $predictionType,
                    'cache_key' => $cacheKey
                ]);
                
                return $memoryResult;
            }
            
            // Try database cache
            $dbResult = DB::table('prediction_cache')
                ->where('cache_key', $cacheKey)
                ->where('expires_at', '>', now())
                ->first();
                
            if ($dbResult) {
                $result = $this->deserializeResult($dbResult->result_data);
                
                // Restore to memory cache
                $ttl = $this->config->getWithOverride('cache_duration_minutes', 60);
                Cache::put($memoryCacheKey, $result, $ttl * 60);
                
                $this->updateCacheStatistics('database_hit', $predictionType);
                
                Log::channel('predictive')->debug('Cache hit (database)', [
                    'type' => $predictionType,
                    'cache_key' => $cacheKey
                ]);
                
                return $result;
            }
            
            // Cache miss
            $this->updateCacheStatistics('cache_miss', $predictionType);
            
            Log::channel('predictive')->debug('Cache miss', [
                'type' => $predictionType,
                'cache_key' => $cacheKey
            ]);
            
            return null;
            
        } catch (Exception $e) {
            Log::channel('predictive')->error('Failed to retrieve cached prediction', [
                'type' => $predictionType,
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            
            return null;
        }
    }

    public function generateCacheKey(string $predictionType, array $filters): string
    {
        // Sort filters to ensure consistent key generation
        ksort($filters);
        
        // Remove null values and empty arrays
        $cleanFilters = array_filter($filters, function($value) {
            return $value !== null && $value !== '' && $value !== [];
        });
        
        // Create base key components
        $keyComponents = [
            'type' => $predictionType,
            'filters' => $cleanFilters,
            'version' => '1.0' // For cache versioning
        ];
        
        // Generate hash
        $keyString = json_encode($keyComponents, 64); // JSON_SORT_KEYS = 64
        $hash = hash('sha256', $keyString);
        
        // Create human-readable prefix
        $prefix = $predictionType;
        if (isset($cleanFilters['clinica_id'])) {
            $prefix .= '_clinic_' . $cleanFilters['clinica_id'];
        }
        if (isset($cleanFilters['algorithm'])) {
            $prefix .= '_' . $cleanFilters['algorithm'];
        }
        
        return $prefix . '_' . substr($hash, 0, 16);
    }

    public function invalidateCache(?string $predictionType = null, ?array $filters = null): int
    {
        try {
            $invalidatedCount = 0;
            
            // Build query for database cache
            $query = DB::table('prediction_cache');
            
            if ($predictionType) {
                $query->where('prediction_type', $predictionType);
            }
            
            if ($filters) {
                $filtersHash = $this->generateFiltersHash($filters);
                $query->where('filters_hash', $filtersHash);
            }
            
            // Get cache keys before deletion for memory cache cleanup
            $cacheEntries = $query->get(['cache_key']);
            
            // Delete from database
            $invalidatedCount = $query->delete();
            
            // Clear corresponding memory cache entries
            foreach ($cacheEntries as $entry) {
                Cache::forget(self::MEMORY_CACHE_PREFIX . $entry->cache_key);
            }
            
            // Update statistics
            $this->updateCacheStatistics('cache_invalidation', $predictionType, $invalidatedCount);
            
            Log::channel('predictive')->info('Cache invalidated', [
                'type' => $predictionType,
                'filters' => $filters,
                'invalidated_count' => $invalidatedCount
            ]);
            
            return $invalidatedCount;
            
        } catch (Exception $e) {
            Log::channel('predictive')->error('Failed to invalidate cache', [
                'type' => $predictionType,
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }

    public function invalidateOnConfigChange(string $configKey): int
    {
        try {
            $affectedTypes = self::CONFIG_PREDICTION_MAP[$configKey] ?? self::PREDICTION_TYPES;
            $totalInvalidated = 0;
            
            foreach ($affectedTypes as $predictionType) {
                $invalidated = $this->invalidateCache($predictionType);
                $totalInvalidated += $invalidated;
            }
            
            Log::channel('predictive')->info('Cache invalidated due to configuration change', [
                'config_key' => $configKey,
                'affected_types' => $affectedTypes,
                'total_invalidated' => $totalInvalidated
            ]);
            
            return $totalInvalidated;
            
        } catch (Exception $e) {
            Log::channel('predictive')->error('Failed to invalidate cache on config change', [
                'config_key' => $configKey,
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }

    public function invalidateOnDataChange(array $dataContext): int
    {
        try {
            $totalInvalidated = 0;
            
            // Determine which prediction types are affected by the data change
            $affectedTypes = $this->determineAffectedTypes($dataContext);
            
            foreach ($affectedTypes as $predictionType) {
                // Invalidate cache entries that might be affected by the data change
                $query = DB::table('prediction_cache')
                    ->where('prediction_type', $predictionType);
                
                // Add date-based filtering if applicable
                if (isset($dataContext['date_range'])) {
                    // Invalidate cache entries that might overlap with the changed data
                    $query->where('created_at', '>=', $dataContext['date_range']['start'] ?? now()->subDays(30));
                }
                
                $cacheEntries = $query->get(['cache_key']);
                $invalidated = $query->delete();
                
                // Clear memory cache
                foreach ($cacheEntries as $entry) {
                    Cache::forget(self::MEMORY_CACHE_PREFIX . $entry->cache_key);
                }
                
                $totalInvalidated += $invalidated;
            }
            
            Log::channel('predictive')->info('Cache invalidated due to data change', [
                'data_context' => $dataContext,
                'affected_types' => $affectedTypes,
                'total_invalidated' => $totalInvalidated
            ]);
            
            return $totalInvalidated;
            
        } catch (Exception $e) {
            Log::channel('predictive')->error('Failed to invalidate cache on data change', [
                'data_context' => $dataContext,
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }

    public function warmCache(array $predictionTypes, array $commonFilters = []): int
    {
        $warmedCount = 0;
        
        try {
            // Default common filter combinations if none provided
            if (empty($commonFilters)) {
                $commonFilters = $this->getCommonFilterCombinations();
            }
            
            foreach ($predictionTypes as $predictionType) {
                if (!in_array($predictionType, self::PREDICTION_TYPES)) {
                    continue;
                }
                
                foreach ($commonFilters as $filters) {
                    // Check if already cached
                    if ($this->getCachedPrediction($predictionType, $filters) !== null) {
                        continue;
                    }
                    
                    // Generate prediction and cache it
                    try {
                        $result = $this->generatePredictionForWarming($predictionType, $filters);
                        if ($result && $this->cachePrediction($predictionType, $filters, $result)) {
                            $warmedCount++;
                        }
                    } catch (Exception $e) {
                        Log::channel('predictive')->warning('Failed to warm cache for prediction', [
                            'type' => $predictionType,
                            'filters' => $filters,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
            
            Log::channel('predictive')->info('Cache warming completed', [
                'types' => $predictionTypes,
                'warmed_count' => $warmedCount
            ]);
            
        } catch (Exception $e) {
            Log::channel('predictive')->error('Cache warming failed', [
                'error' => $e->getMessage()
            ]);
        }
        
        return $warmedCount;
    }

    public function getCacheStatistics(): array
    {
        try {
            // Get current statistics from cache or initialize
            $stats = Cache::get(self::STATS_CACHE_KEY, [
                'memory_hits' => 0,
                'database_hits' => 0,
                'cache_misses' => 0,
                'cache_writes' => 0,
                'cache_invalidations' => 0,
                'by_type' => array_fill_keys(self::PREDICTION_TYPES, [
                    'hits' => 0,
                    'misses' => 0,
                    'writes' => 0
                ]),
                'last_reset' => now()->toISOString()
            ]);
            
            // Get basic database statistics without complex queries
            $totalEntries = DB::table('prediction_cache')->count();
            $activeEntries = DB::table('prediction_cache')
                ->where('expires_at', '>', now())
                ->count();
            
            // Calculate hit rates
            $totalRequests = $stats['memory_hits'] + $stats['database_hits'] + $stats['cache_misses'];
            $hitRate = $totalRequests > 0 ? 
                (($stats['memory_hits'] + $stats['database_hits']) / $totalRequests) * 100 : 0;
            
            return [
                'hit_rate_percentage' => round($hitRate, 2),
                'memory_hit_rate' => $totalRequests > 0 ? round(($stats['memory_hits'] / $totalRequests) * 100, 2) : 0,
                'total_requests' => $totalRequests,
                'memory_hits' => $stats['memory_hits'],
                'database_hits' => $stats['database_hits'],
                'cache_misses' => $stats['cache_misses'],
                'cache_writes' => $stats['cache_writes'],
                'cache_invalidations' => $stats['cache_invalidations'],
                'total_entries' => $totalEntries,
                'active_entries' => $activeEntries,
                'by_type' => $stats['by_type'],
                'last_reset' => $stats['last_reset'],
                'cache_health' => $this->isHealthy()
            ];
            
        } catch (Exception $e) {
            Log::channel('predictive')->error('Failed to get cache statistics', [
                'error' => $e->getMessage()
            ]);
            
            return [
                'hit_rate_percentage' => 0,
                'error' => 'Failed to retrieve statistics'
            ];
        }
    }

    public function cleanExpiredEntries(): int
    {
        try {
            // Get expired entries for memory cache cleanup
            $expiredEntries = DB::table('prediction_cache')
                ->where('expires_at', '<=', now())
                ->get(['cache_key']);
            
            // Delete expired entries from database
            $deletedCount = DB::table('prediction_cache')
                ->where('expires_at', '<=', now())
                ->delete();
            
            // Clear corresponding memory cache entries
            foreach ($expiredEntries as $entry) {
                Cache::forget(self::MEMORY_CACHE_PREFIX . $entry->cache_key);
            }
            
            Log::channel('predictive')->info('Cleaned expired cache entries', [
                'deleted_count' => $deletedCount
            ]);
            
            return $deletedCount;
            
        } catch (Exception $e) {
            Log::channel('predictive')->error('Failed to clean expired cache entries', [
                'error' => $e->getMessage()
            ]);
            
            return 0;
        }
    }

    public function getFallbackResult(string $predictionType, array $filters, Exception $exception)
    {
        try {
            Log::channel('predictive')->warning('Attempting fallback for failed prediction', [
                'type' => $predictionType,
                'filters' => $filters,
                'error' => $exception->getMessage()
            ]);
            
            // Try to find a similar cached result
            $fallbackResult = $this->findSimilarCachedResult($predictionType, $filters);
            
            if ($fallbackResult) {
                Log::channel('predictive')->info('Fallback result found from similar cache', [
                    'type' => $predictionType
                ]);
                
                return $fallbackResult;
            }
            
            // Try to get a basic historical average as last resort
            $historicalFallback = $this->generateHistoricalFallback($predictionType, $filters);
            
            if ($historicalFallback) {
                Log::channel('predictive')->info('Fallback result generated from historical data', [
                    'type' => $predictionType
                ]);
                
                return $historicalFallback;
            }
            
            Log::channel('predictive')->warning('No fallback result available', [
                'type' => $predictionType
            ]);
            
            return null;
            
        } catch (Exception $e) {
            Log::channel('predictive')->error('Fallback generation failed', [
                'type' => $predictionType,
                'error' => $e->getMessage()
            ]);
            
            return null;
        }
    }

    public function isHealthy(): bool
    {
        try {
            // Check database connectivity with a simple query
            $count = DB::table('prediction_cache')->limit(1)->count();
            
            // Check memory cache with a simple test
            $testKey = 'health_check_' . time();
            Cache::put($testKey, 'test', 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);
            
            return $retrieved === 'test';
            
        } catch (Exception $e) {
            Log::channel('predictive')->error('Cache health check failed', [
                'error' => $e->getMessage()
            ]);
            
            return false;
        }
    }

    public function getConfiguration(): array
    {
        return [
            'cache_duration_minutes' => $this->config->getWithOverride('cache_duration_minutes', 60),
            'supported_types' => self::PREDICTION_TYPES,
            'memory_cache_prefix' => self::MEMORY_CACHE_PREFIX,
            'database_table' => 'prediction_cache',
            'config_prediction_mapping' => self::CONFIG_PREDICTION_MAP,
            'health_status' => $this->isHealthy()
        ];
    }

    /**
     * Generate hash for filters to enable efficient lookups
     */
    private function generateFiltersHash(array $filters): string
    {
        ksort($filters);
        $cleanFilters = array_filter($filters, function($value) {
            return $value !== null && $value !== '' && $value !== [];
        });
        
        return hash('sha256', json_encode($cleanFilters, 64)); // JSON_SORT_KEYS = 64
    }

    /**
     * Serialize result for database storage
     */
    private function serializeResult($result): string
    {
        return json_encode($result);
    }

    /**
     * Deserialize result from database storage
     */
    private function deserializeResult(string $serializedResult)
    {
        return json_decode($serializedResult, true);
    }

    /**
     * Extract accuracy metrics from prediction result
     */
    private function extractAccuracyMetrics($result): ?array
    {
        if (is_array($result) && isset($result['accuracy'])) {
            return ['accuracy' => $result['accuracy']];
        }
        
        if (is_object($result) && method_exists($result, 'getAccuracy')) {
            return ['accuracy' => $result->getAccuracy()];
        }
        
        return null;
    }

    /**
     * Update cache statistics
     */
    private function updateCacheStatistics(string $operation, ?string $predictionType = null, int $count = 1): void
    {
        try {
            $stats = Cache::get(self::STATS_CACHE_KEY, [
                'memory_hits' => 0,
                'database_hits' => 0,
                'cache_misses' => 0,
                'cache_writes' => 0,
                'cache_invalidations' => 0,
                'by_type' => array_fill_keys(self::PREDICTION_TYPES, [
                    'hits' => 0,
                    'misses' => 0,
                    'writes' => 0
                ]),
                'last_reset' => now()->toISOString()
            ]);
            
            // Update global statistics
            switch ($operation) {
                case 'memory_hit':
                    $stats['memory_hits'] += $count;
                    if ($predictionType) {
                        $stats['by_type'][$predictionType]['hits'] += $count;
                    }
                    break;
                case 'database_hit':
                    $stats['database_hits'] += $count;
                    if ($predictionType) {
                        $stats['by_type'][$predictionType]['hits'] += $count;
                    }
                    break;
                case 'cache_miss':
                    $stats['cache_misses'] += $count;
                    if ($predictionType) {
                        $stats['by_type'][$predictionType]['misses'] += $count;
                    }
                    break;
                case 'cache_write':
                    $stats['cache_writes'] += $count;
                    if ($predictionType) {
                        $stats['by_type'][$predictionType]['writes'] += $count;
                    }
                    break;
                case 'cache_invalidation':
                    $stats['cache_invalidations'] += $count;
                    break;
            }
            
            // Cache statistics for 24 hours
            Cache::put(self::STATS_CACHE_KEY, $stats, 24 * 60 * 60);
            
        } catch (Exception $e) {
            // Don't let statistics updates break the main functionality
            Log::channel('predictive')->debug('Failed to update cache statistics', [
                'operation' => $operation,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Determine which prediction types are affected by data changes
     */
    private function determineAffectedTypes(array $dataContext): array
    {
        $table = $dataContext['table'] ?? '';
        
        return match($table) {
            'repases' => ['income', 'capacity', 'trends'],
            'gastos' => ['expense'],
            'examenes' => ['capacity'],
            'repase_examenes' => ['income', 'capacity'],
            default => self::PREDICTION_TYPES // If unsure, invalidate all
        };
    }

    /**
     * Get common filter combinations for cache warming
     */
    private function getCommonFilterCombinations(): array
    {
        return [
            [], // No filters (global view)
            ['algorithm' => 'linear_regression'],
            ['algorithm' => 'moving_average'],
            ['algorithm' => 'seasonal'],
            // Add clinic-specific combinations if clinics exist
            // This would be populated dynamically in a real implementation
        ];
    }

    /**
     * Generate prediction for cache warming (simplified version)
     */
    private function generatePredictionForWarming(string $predictionType, array $filters)
    {
        // This is a simplified version for cache warming
        // In a real implementation, this would call the appropriate service
        // For now, return null to indicate no warming prediction available
        return null;
    }

    /**
     * Find similar cached result for fallback
     */
    private function findSimilarCachedResult(string $predictionType, array $filters)
    {
        try {
            // Look for cached results with similar filters
            $similarResults = DB::table('prediction_cache')
                ->where('prediction_type', $predictionType)
                ->where('expires_at', '>', now())
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get();
            
            foreach ($similarResults as $result) {
                // Return the most recent similar result
                return $this->deserializeResult($result->result_data);
            }
            
            return null;
            
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Generate historical fallback result
     */
    private function generateHistoricalFallback(string $predictionType, array $filters)
    {
        // This would generate a basic fallback based on historical averages
        // Implementation would depend on the specific prediction type
        return null;
    }
}