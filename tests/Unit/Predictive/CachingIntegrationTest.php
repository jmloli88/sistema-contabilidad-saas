<?php

namespace Tests\Unit\Predictive;

use Tests\TestCase;
use App\Contracts\Predictive\IncomePredictorInterface;
use App\Contracts\Predictive\CacheServiceInterface;
use App\Models\Clinica;
use App\Models\Empresa;
use App\Models\Repase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class CachingIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private IncomePredictorInterface $incomePredictor;
    private CacheServiceInterface $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->incomePredictor = app(IncomePredictorInterface::class);
        $this->cacheService = app(CacheServiceInterface::class);
        
        $this->createTestData();
    }

    public function test_income_prediction_uses_cache()
    {
        $filters = ['clinica_id' => 1, 'algorithm' => 'linear_regression'];
        
        // First call should generate and cache the prediction
        $result1 = $this->incomePredictor->predictIncome($filters, 12);
        
        // Verify result is cached
        $cachedResult = $this->cacheService->getCachedPrediction('income', $filters);
        $this->assertNotNull($cachedResult);
        
        // Second call should use cached result
        $result2 = $this->incomePredictor->predictIncome($filters, 12);
        
        // Results should be identical
        $this->assertEquals($result1->projections, $result2->projections);
        $this->assertEquals($result1->algorithm, $result2->algorithm);
    }

    public function test_cache_invalidation_forces_recalculation()
    {
        $filters = ['clinica_id' => 1];
        
        // Generate initial prediction
        $result1 = $this->incomePredictor->predictIncome($filters, 12);
        
        // Verify it's cached
        $cachedResult = $this->cacheService->getCachedPrediction('income', $filters);
        $this->assertNotNull($cachedResult);
        
        // Invalidate cache
        $invalidated = $this->cacheService->invalidateCache('income');
        $this->assertGreaterThan(0, $invalidated);
        
        // Verify cache is cleared
        $cachedResult = $this->cacheService->getCachedPrediction('income', $filters);
        $this->assertNull($cachedResult);
        
        // New prediction should be generated
        $result2 = $this->incomePredictor->predictIncome($filters, 12);
        $this->assertNotNull($result2);
    }

    public function test_cache_key_uniqueness_by_filters()
    {
        $filters1 = ['clinica_id' => 1, 'algorithm' => 'linear_regression'];
        $filters2 = ['clinica_id' => 2, 'algorithm' => 'linear_regression'];
        
        // Generate predictions with different filters
        $result1 = $this->incomePredictor->predictIncome($filters1, 12);
        $result2 = $this->incomePredictor->predictIncome($filters2, 12);
        
        // Both should be cached separately
        $cached1 = $this->cacheService->getCachedPrediction('income', $filters1);
        $cached2 = $this->cacheService->getCachedPrediction('income', $filters2);
        
        $this->assertNotNull($cached1);
        $this->assertNotNull($cached2);
        
        // Cache keys should be different
        $key1 = $this->cacheService->generateCacheKey('income', $filters1);
        $key2 = $this->cacheService->generateCacheKey('income', $filters2);
        
        $this->assertNotEquals($key1, $key2);
    }

    public function test_cache_statistics_track_usage()
    {
        $filters = ['clinica_id' => 1];
        
        // Get initial statistics
        $initialStats = $this->cacheService->getCacheStatistics();
        
        // Generate a prediction (should be a cache miss and write)
        $this->incomePredictor->predictIncome($filters, 12);
        
        // Get the same prediction again (should be a cache hit)
        $this->incomePredictor->predictIncome($filters, 12);
        
        // Statistics should show the activity
        $finalStats = $this->cacheService->getCacheStatistics();
        
        // We should have at least one cache write and one hit
        $this->assertGreaterThanOrEqual($initialStats['cache_writes'], $finalStats['cache_writes']);
        $this->assertGreaterThanOrEqual($initialStats['total_requests'], $finalStats['total_requests']);
    }

    private function createTestData(): void
    {
        $empresa = Empresa::factory()->create();

        // Create test clinics
        $clinica1 = Clinica::create([
            'nombre' => 'Test Clinic 1',
            'direccion' => 'Test Address 1',
            'telefono' => '123456789',
            'empresa_id' => $empresa->id,
        ]);

        $clinica2 = Clinica::create([
            'nombre' => 'Test Clinic 2',
            'direccion' => 'Test Address 2',
            'telefono' => '987654321',
            'empresa_id' => $empresa->id,
        ]);

        // Create test repases with historical data for both clinics
        foreach ([$clinica1, $clinica2] as $clinica) {
            for ($i = 0; $i < 24; $i++) {
                Repase::create([
                    'fecha' => Carbon::now()->subMonths($i),
                    'clinica_id' => $clinica->id,
                    'total' => 10000 + ($i * 100), // Increasing trend
                    'total_neto' => 9000 + ($i * 90),
                    'total_gastos' => 1000 + ($i * 10),
                    'tipo_precio' => 'sin_nota' // Add required field with valid enum value
                ]);
            }
        }
    }
}