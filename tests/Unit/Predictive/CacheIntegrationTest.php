<?php

namespace Tests\Unit\Predictive;

use Tests\TestCase;
use App\Contracts\Predictive\CacheServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CacheIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cache_service_can_be_resolved_from_container()
    {
        $cacheService = app(CacheServiceInterface::class);
        
        $this->assertInstanceOf(CacheServiceInterface::class, $cacheService);
    }

    public function test_cache_service_basic_functionality()
    {
        $cacheService = app(CacheServiceInterface::class);
        
        // Test configuration
        $config = $cacheService->getConfiguration();
        $this->assertIsArray($config);
        
        // Test health check
        $health = $cacheService->isHealthy();
        $this->assertIsBool($health);
        
        // Test key generation
        $key = $cacheService->generateCacheKey('income', ['test' => 'value']);
        $this->assertIsString($key);
        $this->assertStringContainsString('income', $key);
    }
}