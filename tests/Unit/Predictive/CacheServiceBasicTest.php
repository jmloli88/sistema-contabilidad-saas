<?php

namespace Tests\Unit\Predictive;

use Tests\TestCase;
use App\Services\Predictive\CacheService;
use App\Contracts\Predictive\CacheServiceInterface;
use App\Contracts\PredictiveConfigInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

class CacheServiceBasicTest extends TestCase
{
    use RefreshDatabase;

    private CacheServiceInterface $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $config = $this->createMock(PredictiveConfigInterface::class);
        $config->method('getWithOverride')->willReturn(60);
        
        $this->cacheService = new CacheService($config);
    }

    public function test_cache_service_can_be_instantiated()
    {
        $this->assertInstanceOf(CacheServiceInterface::class, $this->cacheService);
    }

    public function test_cache_key_generation_is_consistent()
    {
        $predictionType = 'income';
        $filters = ['clinica_id' => 1];
        
        $key1 = $this->cacheService->generateCacheKey($predictionType, $filters);
        $key2 = $this->cacheService->generateCacheKey($predictionType, $filters);
        
        $this->assertEquals($key1, $key2);
        $this->assertStringContainsString('income', $key1);
    }

    public function test_cache_prediction_stores_in_database()
    {
        $predictionType = 'income';
        $filters = ['clinica_id' => 1];
        $result = ['test' => 'data'];
        
        $success = $this->cacheService->cachePrediction($predictionType, $filters, $result);
        
        $this->assertTrue($success);
        
        $cached = DB::table('prediction_cache')
            ->where('prediction_type', $predictionType)
            ->first();
        
        $this->assertNotNull($cached);
        $this->assertEquals($predictionType, $cached->prediction_type);
    }

    public function test_get_cached_prediction_returns_stored_result()
    {
        $predictionType = 'income';
        $filters = ['clinica_id' => 1];
        $result = ['test' => 'data'];
        
        $this->cacheService->cachePrediction($predictionType, $filters, $result);
        $cachedResult = $this->cacheService->getCachedPrediction($predictionType, $filters);
        
        $this->assertNotNull($cachedResult);
        $this->assertEquals($result, $cachedResult);
    }

    public function test_invalidate_cache_removes_entries()
    {
        $predictionType = 'income';
        $filters = ['clinica_id' => 1];
        $result = ['test' => 'data'];
        
        $this->cacheService->cachePrediction($predictionType, $filters, $result);
        $this->assertEquals(1, DB::table('prediction_cache')->count());
        
        $invalidated = $this->cacheService->invalidateCache($predictionType);
        
        $this->assertEquals(1, $invalidated);
        $this->assertEquals(0, DB::table('prediction_cache')->count());
    }

    public function test_cache_statistics_returns_array()
    {
        $stats = $this->cacheService->getCacheStatistics();
        
        $this->assertIsArray($stats);
        $this->assertArrayHasKey('hit_rate_percentage', $stats);
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
        $this->assertArrayHasKey('supported_types', $config);
        $this->assertEquals(['income', 'expense', 'capacity', 'trends'], $config['supported_types']);
    }
}