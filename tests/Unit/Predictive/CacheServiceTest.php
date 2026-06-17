<?php

namespace Tests\Unit\Predictive;

use Tests\TestCase;
use App\Services\Predictive\CacheService;
use App\Contracts\Predictive\CacheServiceInterface;
use App\Contracts\PredictiveConfigInterface;
use App\DTOs\Predictive\PredictionResult;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CacheServiceTest extends TestCase
{
    use RefreshDatabase;

    private CacheServiceInterface $cacheService;
    private PredictiveConfigInterface $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->config = $this->createMock(PredictiveConfigInterface::class);
        $this->config->method('getWithOverride')
                    ->willReturn(60); // Default 60 minutes TTL
        
        $this->cacheService = new CacheService($this->config);
    }

    public function test_cache_key_generation_is_consistent()
    {
        $predictionType = 'income';
        $filters = ['clinica_id' => 1, 'algorithm' => 'linear_regression'];
        
        $key1 = $this->cacheService->generateCacheKey($predictionType, $filters);
        $key2 = $this->cacheService->generateCacheKey($predictionType, $filters);
        
        $this->assertEquals($key1, $key2);
        $this->assertStringContains('income', $key1);
        $this->assertStringContains('clinic_1', $key1);
        $this->assertStringContains('linear_regression', $key1);
    }

    public function test_cache_key_generation_handles_filter_order()
    {
        $predictionType = 'income';
        $filters1 = ['clinica_id' => 1, 'algorithm' => 'linear_regression'];
        $filters2 = ['algorithm' => 'linear_regression', 'clinica_id' => 1];
        
        $key1 = $this->cacheService->generateCacheKey($predictionType, $filters1);
        $key2 = $this->cacheService->generateCacheKey($predictionType, $filters2);
        
        $this->assertEquals($key1, $key2, 'Cache keys should be identical regardless of filter order');
    }

    public function test_cache_key_generation_ignores_null_values()
    {
        $predictionType = 'income';
        $filters1 = ['clinica_id' => 1, 'algorithm' => 'linear_regression'];
        $filters2 = ['clinica_id' => 1, 'algorithm' => 'linear_regression', 'empty_filter' => null];
        
        $key1 = $this->cacheService->generateCacheKey($predictionType, $filters1);
        $key2 = $this->cacheService->generateCacheKey($predictionType, $filters2);
        
        $this->assertEquals($key1, $key2, 'Cache keys should ignore null values');
    }

    public function test_cache_prediction_stores_in_database()
    {
        $predictionType = 'income';
        $filters = ['clinica_id' => 1];
        $result = [
            'projections' => ['3_months' => 10000, '6_months' => 20000, '12_months' => 40000],
            'algorithm' => 'linear_regression',
            'accuracy' => 85.5
        ];
        
        $success = $this->cacheService->cachePrediction($predictionType, $filters, $result);
        
        $this->assertTrue($success);
        
        // Verify database storage
        $cached = DB::table('prediction_cache')
            ->where('prediction_type', $predictionType)
            ->first();
        
        $this->assertNotNull($cached);
        $this->assertEquals($predictionType, $cached->prediction_type);
        $this->assertNotNull($cached->result_data);
        $this->assertNotNull($cached->expires_at);
    }

    public function test_get_cached_prediction_returns_stored_result()
    {
        $predictionType = 'income';
        $filters = ['clinica_id' => 1];
        $result = [
            'projections' => ['3_months' => 10000, '6_months' => 20000, '12_months' => 40000],
            'algorithm' => 'linear_regression',
            'accuracy' => 85.5
        ];
        
        // Cache the result
        $this->cacheService->cachePrediction($predictionType, $filters, $result);
        
        // Retrieve from cache
        $cachedResult = $this->cacheService->getCachedPrediction($predictionType, $filters);
        
        $this->assertNotNull($cachedResult);
        $this->assertEquals($result['projections'], $cachedResult['projections']);
        $this->assertEquals($result['algorithm'], $cachedResult['algorithm']);
        $this->assertEquals($result['accuracy'], $cachedResult['accuracy']);
    }

    public function test_get_cached_prediction_returns_null_for_expired_entries()
    {
        $predictionType = 'income';
        $filters = ['clinica_id' => 1];
        $result = ['test' => 'data'];
        
        // Insert expired cache entry directly
        $cacheKey = $this->cacheService->generateCacheKey($predictionType, $filters);
        DB::table('prediction_cache')->insert([
            'cache_key' => $cacheKey,
            'prediction_type' => $predictionType,
            'filters_hash' => hash('sha256', json_encode($filters)),
            'result_data' => json_encode($result),
            'expires_at' => Carbon::now()->subHour(), // Expired 1 hour ago
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $cachedResult = $this->cacheService->getCachedPrediction($predictionType, $filters);
        
        $this->assertNull($cachedResult, 'Expired cache entries should return null');
    }

    public function test_invalidate_cache_removes_entries()
    {
        $predictionType = 'income';
        $filters1 = ['clinica_id' => 1];
        $filters2 = ['clinica_id' => 2];
        $result = ['test' => 'data'];
        
        // Cache multiple results
        $this->cacheService->cachePrediction($predictionType, $filters1, $result);
        $this->cacheService->cachePrediction($predictionType, $filters2, $result);
        $this->cacheService->cachePrediction('expense', $filters1, $result);
        
        // Verify entries exist
        $this->assertEquals(3, DB::table('prediction_cache')->count());
        
        // Invalidate specific type
        $invalidated = $this->cacheService->invalidateCache($predictionType);
        
        $this->assertEquals(2, $invalidated);
        $this->assertEquals(1, DB::table('prediction_cache')->count());
        
        // Verify only expense entry remains
        $remaining = DB::table('prediction_cache')->first();
        $this->assertEquals('expense', $remaining->prediction_type);
    }

    public function test_invalidate_cache_removes_all_when_no_type_specified()
    {
        $result = ['test' => 'data'];
        
        // Cache multiple results of different types
        $this->cacheService->cachePrediction('income', ['clinica_id' => 1], $result);
        $this->cacheService->cachePrediction('expense', ['clinica_id' => 1], $result);
        $this->cacheService->cachePrediction('capacity', ['clinica_id' => 1], $result);
        
        $this->assertEquals(3, DB::table('prediction_cache')->count());
        
        // Invalidate all
        $invalidated = $this->cacheService->invalidateCache();
        
        $this->assertEquals(3, $invalidated);
        $this->assertEquals(0, DB::table('prediction_cache')->count());
    }

    public function test_invalidate_on_config_change_affects_correct_types()
    {
        $result = ['test' => 'data'];
        
        // Cache results for all types
        $this->cacheService->cachePrediction('income', [], $result);
        $this->cacheService->cachePrediction('expense', [], $result);
        $this->cacheService->cachePrediction('capacity', [], $result);
        $this->cacheService->cachePrediction('trends', [], $result);
        
        $this->assertEquals(4, DB::table('prediction_cache')->count());
        
        // Change expense-specific configuration
        $invalidated = $this->cacheService->invalidateOnConfigChange('expense_alert_threshold');
        
        $this->assertEquals(1, $invalidated);
        $this->assertEquals(3, DB::table('prediction_cache')->count());
        
        // Verify only expense cache was invalidated
        $remaining = DB::table('prediction_cache')
            ->pluck('prediction_type')
            ->toArray();
        
        $this->assertContains('income', $remaining);
        $this->assertContains('capacity', $remaining);
        $this->assertContains('trends', $remaining);
        $this->assertNotContains('expense', $remaining);
    }

    public function test_clean_expired_entries_removes_only_expired()
    {
        $result = ['test' => 'data'];
        
        // Insert valid and expired entries directly
        $validKey = 'valid_entry';
        $expiredKey = 'expired_entry';
        
        DB::table('prediction_cache')->insert([
            [
                'cache_key' => $validKey,
                'prediction_type' => 'income',
                'filters_hash' => 'hash1',
                'result_data' => json_encode($result),
                'expires_at' => Carbon::now()->addHour(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'cache_key' => $expiredKey,
                'prediction_type' => 'expense',
                'filters_hash' => 'hash2',
                'result_data' => json_encode($result),
                'expires_at' => Carbon::now()->subHour(),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
        
        $this->assertEquals(2, DB::table('prediction_cache')->count());
        
        $cleaned = $this->cacheService->cleanExpiredEntries();
        
        $this->assertEquals(1, $cleaned);
        $this->assertEquals(1, DB::table('prediction_cache')->count());
        
        // Verify only valid entry remains
        $remaining = DB::table('prediction_cache')->first();
        $this->assertEquals($validKey, $remaining->cache_key);
    }

    public function test_cache_statistics_returns_correct_structure()
    {
        $stats = $this->cacheService->getCacheStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('hit_rate_percentage', $stats);
        $this->assertArrayHasKey('memory_hit_rate', $stats);
        $this->assertArrayHasKey('total_requests', $stats);
        $this->assertArrayHasKey('memory_hits', $stats);
        $this->assertArrayHasKey('database_hits', $stats);
        $this->assertArrayHasKey('cache_misses', $stats);
        $this->assertArrayHasKey('cache_writes', $stats);
        $this->assertArrayHasKey('cache_invalidations', $stats);
        $this->assertArrayHasKey('by_type', $stats);
        $this->assertArrayHasKey('cache_health', $stats);
    }

    public function test_is_healthy_returns_boolean()
    {
        $health = $this->cacheService->isHealthy();
        
        $this->assertIsBool($health);
    }

    public function test_get_configuration_returns_expected_structure()
    {
        $config = $this->cacheService->getConfiguration();
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('cache_duration_minutes', $config);
        $this->assertArrayHasKey('supported_types', $config);
        $this->assertArrayHasKey('memory_cache_prefix', $config);
        $this->assertArrayHasKey('database_table', $config);
        $this->assertArrayHasKey('config_prediction_mapping', $config);
        $this->assertArrayHasKey('health_status', $config);
        
        $this->assertEquals(['income', 'expense', 'capacity', 'trends'], $config['supported_types']);
        $this->assertEquals('prediction_cache', $config['database_table']);
    }

    public function test_cache_prediction_rejects_invalid_types()
    {
        $result = ['test' => 'data'];
        
        $success = $this->cacheService->cachePrediction('invalid_type', [], $result);
        
        $this->assertFalse($success);
        $this->assertEquals(0, DB::table('prediction_cache')->count());
    }

    public function test_fallback_result_returns_null_when_no_fallback_available()
    {
        $exception = new \Exception('Test exception');
        
        $fallback = $this->cacheService->getFallbackResult('income', [], $exception);
        
        $this->assertNull($fallback);
    }

    public function test_memory_cache_integration()
    {
        $predictionType = 'income';
        $filters = ['clinica_id' => 1];
        $result = ['test' => 'data'];
        
        // Cache the result
        $this->cacheService->cachePrediction($predictionType, $filters, $result);
        
        // Verify it's in memory cache
        $cacheKey = $this->cacheService->generateCacheKey($predictionType, $filters);
        $memoryKey = 'predictive_memory_' . $cacheKey;
        
        $memoryCached = Cache::get($memoryKey);
        $this->assertEquals($result, $memoryCached);
        
        // Clear database but keep memory cache
        DB::table('prediction_cache')->delete();
        
        // Should still return from memory cache
        $cachedResult = $this->cacheService->getCachedPrediction($predictionType, $filters);
        $this->assertEquals($result, $cachedResult);
    }
}