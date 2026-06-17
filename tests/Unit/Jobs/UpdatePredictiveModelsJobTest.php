<?php

namespace Tests\Unit\Jobs;

use Tests\TestCase;
use App\Jobs\UpdatePredictiveModelsJob;
use App\Contracts\Predictive\IncomePredictorInterface;
use App\Contracts\Predictive\ExpenseForecasterInterface;
use App\Contracts\Predictive\CapacityAnalyzerInterface;
use App\Contracts\Predictive\TrendDetectorInterface;
use App\Contracts\Predictive\CacheServiceInterface;
use App\Contracts\PredictiveConfigInterface;
use App\DTOs\Predictive\PredictionResult;
use App\DTOs\Predictive\ExpenseForecast;
use App\DTOs\Predictive\CapacityAnalysis;
use App\DTOs\Predictive\SeasonalAnalysis;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Mockery;

class UpdatePredictiveModelsJobTest extends TestCase
{
    use RefreshDatabase;

    private $incomePredictor;
    private $expenseForecaster;
    private $capacityAnalyzer;
    private $trendDetector;
    private $cacheService;
    private $config;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create mocks for all dependencies
        $this->incomePredictor = Mockery::mock(IncomePredictorInterface::class);
        $this->expenseForecaster = Mockery::mock(ExpenseForecasterInterface::class);
        $this->capacityAnalyzer = Mockery::mock(CapacityAnalyzerInterface::class);
        $this->trendDetector = Mockery::mock(TrendDetectorInterface::class);
        $this->cacheService = Mockery::mock(CacheServiceInterface::class);
        $this->config = Mockery::mock(PredictiveConfigInterface::class);
        
        // Bind mocks to container
        $this->app->instance(IncomePredictorInterface::class, $this->incomePredictor);
        $this->app->instance(ExpenseForecasterInterface::class, $this->expenseForecaster);
        $this->app->instance(CapacityAnalyzerInterface::class, $this->capacityAnalyzer);
        $this->app->instance(TrendDetectorInterface::class, $this->trendDetector);
        $this->app->instance(CacheServiceInterface::class, $this->cacheService);
        $this->app->instance(PredictiveConfigInterface::class, $this->config);
    }

    public function test_job_executes_successfully_with_all_services()
    {
        // Setup expectations for successful execution
        $this->setupSuccessfulMocks();
        
        // Create and execute job
        $job = new UpdatePredictiveModelsJob();
        
        // Execute job
        $job->handle(
            $this->incomePredictor,
            $this->expenseForecaster,
            $this->capacityAnalyzer,
            $this->trendDetector,
            $this->cacheService,
            $this->config
        );
        
        // Verify cache was updated
        $this->assertTrue(Cache::has('predictive_last_successful_update'));
    }

    public function test_job_creates_backup_before_update()
    {
        $this->setupSuccessfulMocks();
        
        // Ensure prediction_cache table exists
        $this->createPredictionCacheTables();
        
        $job = new UpdatePredictiveModelsJob();
        
        $job->handle(
            $this->incomePredictor,
            $this->expenseForecaster,
            $this->capacityAnalyzer,
            $this->trendDetector,
            $this->cacheService,
            $this->config
        );
        
        // Check that backup tables were created
        $tables = DB::select("SELECT name FROM sqlite_master WHERE type='table' AND name LIKE 'prediction_%_backup_%'");
        $this->assertGreaterThan(0, count($tables));
    }

    public function test_job_handles_service_failures_gracefully()
    {
        // Setup cache service to be healthy
        $this->cacheService->shouldReceive('isHealthy')->andReturn(true);
        $this->cacheService->shouldReceive('invalidateCache')->andReturn(0);
        $this->cacheService->shouldReceive('cleanExpiredEntries')->andReturn(0);
        $this->cacheService->shouldReceive('warmCache')->andReturn(0);
        
        // Setup config
        $this->config->shouldReceive('getWithOverride')
            ->with('active_algorithms', Mockery::any())
            ->andReturn(['linear_regression']);
        
        // Make income predictor fail
        $this->incomePredictor->shouldReceive('predictIncome')
            ->andThrow(new \Exception('Income prediction failed'));
        
        // Other services should still work
        $this->expenseForecaster->shouldReceive('forecastExpenses')
            ->andReturn(new ExpenseForecast([], [], 0.5, [], []));
        
        $this->capacityAnalyzer->shouldReceive('analyzeCurrentCapacity')
            ->andReturn(new CapacityAnalysis(75.0, [], null, [], []));
        
        $this->createPredictionCacheTables();
        
        $job = new UpdatePredictiveModelsJob();
        
        // Job should complete despite one service failing
        $job->handle(
            $this->incomePredictor,
            $this->expenseForecaster,
            $this->capacityAnalyzer,
            $this->trendDetector,
            $this->cacheService,
            $this->config
        );
        
        $this->assertTrue(Cache::has('predictive_last_successful_update'));
    }

    public function test_job_fails_when_too_many_services_fail()
    {
        // Setup cache service to be healthy
        $this->cacheService->shouldReceive('isHealthy')->andReturn(true);
        
        // Setup config
        $this->config->shouldReceive('getWithOverride')
            ->with('active_algorithms', Mockery::any())
            ->andReturn(['linear_regression']);
        
        // Make multiple services fail
        $this->incomePredictor->shouldReceive('predictIncome')
            ->andThrow(new \Exception('Income prediction failed'));
        
        $this->expenseForecaster->shouldReceive('forecastExpenses')
            ->andThrow(new \Exception('Expense forecast failed'));
        
        $this->capacityAnalyzer->shouldReceive('analyzeCurrentCapacity')
            ->andThrow(new \Exception('Capacity analysis failed'));
        
        $this->createPredictionCacheTables();
        
        $job = new UpdatePredictiveModelsJob();
        
        // Job should fail when too many services fail
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Too many model recalculation errors');
        
        $job->handle(
            $this->incomePredictor,
            $this->expenseForecaster,
            $this->capacityAnalyzer,
            $this->trendDetector,
            $this->cacheService,
            $this->config
        );
    }

    public function test_job_validates_execution_time()
    {
        $this->setupSuccessfulMocks();
        $this->createPredictionCacheTables();
        
        $job = new UpdatePredictiveModelsJob();
        
        // Execute job
        $job->handle(
            $this->incomePredictor,
            $this->expenseForecaster,
            $this->capacityAnalyzer,
            $this->trendDetector,
            $this->cacheService,
            $this->config
        );
        
        // Job should complete successfully (execution time should be well under 10 minutes for tests)
        $this->assertTrue(Cache::has('predictive_last_successful_update'));
    }

    private function setupSuccessfulMocks(): void
    {
        // Cache service mocks
        $this->cacheService->shouldReceive('isHealthy')->andReturn(true);
        $this->cacheService->shouldReceive('invalidateCache')->andReturn(10);
        $this->cacheService->shouldReceive('cleanExpiredEntries')->andReturn(5);
        $this->cacheService->shouldReceive('warmCache')->andReturn(8);
        
        // Config mocks
        $this->config->shouldReceive('getWithOverride')
            ->with('active_algorithms', Mockery::any())
            ->andReturn(['linear_regression', 'moving_average']);
        
        // Income predictor mocks
        $this->incomePredictor->shouldReceive('predictIncome')
            ->andReturn(new PredictionResult(
                projections: ['3_months' => 50000, '6_months' => 100000, '12_months' => 200000],
                algorithm: 'linear_regression',
                metadata: [],
                accuracy: 85.5
            ));
        
        // Expense forecaster mocks
        $this->expenseForecaster->shouldReceive('forecastExpenses')
            ->andReturn(new ExpenseForecast(
                projections: ['3_months' => ['total' => 30000]],
                categoryBreakdown: [],
                correlation: 0.75,
                alerts: [],
                metadata: []
            ));
        
        // Capacity analyzer mocks
        $this->capacityAnalyzer->shouldReceive('analyzeCurrentCapacity')
            ->andReturn(new CapacityAnalysis(
                currentUtilization: 75.0,
                clinicUtilization: [],
                projectedSaturationDate: null,
                bottlenecks: [],
                recommendations: []
            ));
        
        // Trend detector mocks (optional - only if sufficient data)
        $this->trendDetector->shouldReceive('detectSeasonalPatterns')
            ->andReturn(new SeasonalAnalysis(
                monthlyPatterns: [],
                seasonalStrength: 0.8,
                confidenceIntervals: [],
                metadata: []
            ));
    }

    private function createPredictionCacheTables(): void
    {
        // Create prediction_cache table if it doesn't exist
        if (!DB::getSchemaBuilder()->hasTable('prediction_cache')) {
            DB::statement('
                CREATE TABLE prediction_cache (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    cache_key VARCHAR(255) NOT NULL UNIQUE,
                    prediction_type VARCHAR(100) NOT NULL,
                    filters_hash VARCHAR(255) NOT NULL,
                    result_data TEXT NOT NULL,
                    accuracy_metrics TEXT,
                    expires_at TIMESTAMP NOT NULL,
                    created_at TIMESTAMP,
                    updated_at TIMESTAMP
                )
            ');
        }
        
        // Create prediction_accuracy_log table if it doesn't exist
        if (!DB::getSchemaBuilder()->hasTable('prediction_accuracy_log')) {
            DB::statement('
                CREATE TABLE prediction_accuracy_log (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    prediction_type VARCHAR(100) NOT NULL,
                    algorithm VARCHAR(100) NOT NULL,
                    prediction_date DATE NOT NULL,
                    actual_date DATE NOT NULL,
                    predicted_value DECIMAL(15,2) NOT NULL,
                    actual_value DECIMAL(15,2) NOT NULL,
                    absolute_error DECIMAL(15,2) NOT NULL,
                    percentage_error DECIMAL(8,4) NOT NULL,
                    created_at TIMESTAMP
                )
            ');
        }
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}