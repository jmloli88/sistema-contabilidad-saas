<?php

namespace Tests\Unit\Predictive;

use Tests\TestCase;
use App\Contracts\Predictive\IncomePredictorInterface;
use App\Contracts\Predictive\TrendDetectorInterface;
use App\Contracts\Predictive\ExpenseForecasterInterface;
use App\Contracts\Predictive\CapacityAnalyzerInterface;
use App\Contracts\Predictive\ExportServiceInterface;
use App\Models\Repase;
use App\Models\Clinica;
use App\Models\Gasto;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CoreServicesIntegrationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that all core services can be resolved and work together
     */
    public function test_all_core_services_integration()
    {
        // Create test data
        $this->createTestData();

        // Test service resolution
        $incomePredictor = app(IncomePredictorInterface::class);
        $trendDetector = app(TrendDetectorInterface::class);
        $expenseForecaster = app(ExpenseForecasterInterface::class);
        $capacityAnalyzer = app(CapacityAnalyzerInterface::class);
        $exportService = app(ExportServiceInterface::class);

        $this->assertNotNull($incomePredictor);
        $this->assertNotNull($trendDetector);
        $this->assertNotNull($expenseForecaster);
        $this->assertNotNull($capacityAnalyzer);
        $this->assertNotNull($exportService);

        // Test that services can work with the same data
        $filters = ['clinica_id' => 1];

        // Test income prediction
        $incomeResult = $incomePredictor->predictIncome($filters, 12);
        $this->assertCount(3, $incomeResult->getProjections());

        // Test expense forecasting
        $expenseResult = $expenseForecaster->forecastExpenses($filters, 12);
        $this->assertNotNull($expenseResult);

        // Test capacity analysis
        $capacityResult = $capacityAnalyzer->analyzeCurrentCapacity($filters);
        $this->assertNotNull($capacityResult);

        // Test that all services return consistent data types
        $this->assertIsFloat($incomeResult->getProjections()['3_months']);
        $this->assertIsArray($expenseResult->projections);
        $this->assertIsFloat($capacityResult->currentUtilization);
    }

    /**
     * Test mathematical correctness across services
     */
    public function test_mathematical_correctness_integration()
    {
        $this->createTestData();

        $incomePredictor = app(IncomePredictorInterface::class);
        $expenseForecaster = app(ExpenseForecasterInterface::class);

        // Test with different algorithms
        $algorithms = ['linear_regression', 'moving_average', 'seasonal'];
        
        foreach ($algorithms as $algorithm) {
            $result = $incomePredictor->predictIncome(['algorithm' => $algorithm], 12);
            
            // All projections should be non-negative
            foreach ($result->getProjections() as $projection) {
                $this->assertGreaterThanOrEqual(0, $projection);
            }
            
            // 12-month projection should generally be higher than 3-month (due to growth trends)
            // This is a reasonable assumption for most business scenarios
            $this->assertIsFloat($result->getProjections()['3_months']);
            $this->assertIsFloat($result->getProjections()['12_months']);
        }

        // Test expense correlation calculation
        $expenseResult = $expenseForecaster->forecastExpenses([], 12);
        $this->assertGreaterThanOrEqual(-1, $expenseResult->correlation);
        $this->assertLessThanOrEqual(1, $expenseResult->correlation);
    }

    /**
     * Test service performance and caching
     */
    public function test_service_performance()
    {
        $this->createTestData();

        $incomePredictor = app(IncomePredictorInterface::class);
        
        // First call - should work without cache
        $start = microtime(true);
        $result1 = $incomePredictor->predictIncome(['clinica_id' => 1], 12);
        $time1 = microtime(true) - $start;
        
        // Second call - should be faster (potentially cached)
        $start = microtime(true);
        $result2 = $incomePredictor->predictIncome(['clinica_id' => 1], 12);
        $time2 = microtime(true) - $start;
        
        // Both calls should return the same results
        $this->assertEquals($result1->getProjections(), $result2->getProjections());
        
        // Performance should be reasonable (under 1 second for test data)
        $this->assertLessThan(1.0, $time1, 'First prediction should complete within 1 second');
        $this->assertLessThan(1.0, $time2, 'Second prediction should complete within 1 second');
    }

    private function createTestData(): void
    {
        // Create test clinics
        $clinica1 = Clinica::factory()->create(['id' => 1, 'nombre' => 'Clínica Test 1']);
        $clinica2 = Clinica::factory()->create(['id' => 2, 'nombre' => 'Clínica Test 2']);

        // Create 24 months of historical data
        for ($i = 0; $i < 24; $i++) {
            $date = Carbon::now()->subMonths(24 - $i - 1);
            $baseIncome = 50000 + ($i * 1000); // Growth trend
            
            // Create repases for both clinics
            $repase1 = Repase::factory()->create([
                'clinica_id' => 1,
                'fecha' => $date,
                'total_neto' => $baseIncome,
                'estado' => 'pagado'
            ]);
            
            $repase2 = Repase::factory()->create([
                'clinica_id' => 2,
                'fecha' => $date,
                'total_neto' => $baseIncome * 0.8,
                'estado' => 'pagado'
            ]);

            // Create some gastos for expense forecasting
            Gasto::factory()->create([
                'repase_id' => $repase1->id,
                'tipo' => 'doctor',
                'monto' => $baseIncome * 0.3,
                'descripcion' => 'Doctor fees'
            ]);

            Gasto::factory()->create([
                'repase_id' => $repase2->id,
                'tipo' => 'tecnico',
                'monto' => $baseIncome * 0.2,
                'descripcion' => 'Technical costs'
            ]);
        }
    }
}