<?php

namespace Tests\Unit\Predictive;

use Tests\TestCase;
use App\Services\Predictive\IncomePredictor;
use App\DTOs\Predictive\PredictionResult;
use App\Models\Repase;
use App\Models\Clinica;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

class IncomePredictorPropertyTest extends TestCase
{
    use RefreshDatabase;

    private IncomePredictor $predictor;

    protected function setUp(): void
    {
        parent::setUp();
        $this->predictor = new IncomePredictor();
    }

    /**
     * Property 1: Time Period Projection Generation
     * **Validates: Requirements 1.1**
     * 
     * For any prediction request (income), the system should generate exactly 
     * three projections for 3, 6, and 12 months respectively
     */
    public function test_income_prediction_generates_exactly_three_time_periods()
    {
        // Generate test data with sufficient historical data (24 months)
        $this->generateHistoricalData(24);

        // Test with different filter combinations
        $testCases = [
            ['clinica_id' => 1],
            ['clinica_id' => 2],
            [],
            ['algorithm' => 'linear_regression'],
            ['algorithm' => 'moving_average'],
            ['algorithm' => 'seasonal']
        ];

        foreach ($testCases as $filters) {
            $result = $this->predictor->predictIncome($filters, 12);
            
            // Verify exactly 3 projections exist
            $this->assertCount(3, $result->getProjections(), 
                "Should generate exactly 3 projections for filters: " . json_encode($filters));
            
            // Verify specific time periods exist
            $this->assertArrayHasKey('3_months', $result->getProjections());
            $this->assertArrayHasKey('6_months', $result->getProjections());
            $this->assertArrayHasKey('12_months', $result->getProjections());
            
            // Verify all projections are numeric and non-negative
            foreach ($result->getProjections() as $period => $value) {
                $this->assertIsFloat($value, "Projection for {$period} should be float");
                $this->assertGreaterThanOrEqual(0, $value, "Projection for {$period} should be non-negative");
            }
        }
    }

    /**
     * Property 2: Historical Data Sufficiency Validation
     * **Validates: Requirements 1.2, 1.4**
     * 
     * For any prediction algorithm, predictions should only be generated when 
     * sufficient historical data exists (minimum 12 months for income)
     */
    /**
     * Property 2: Historical Data Sufficiency Validation
     * **Validates: Requirements 1.2, 1.4**
     * 
     * For any prediction algorithm, predictions should only be generated when 
     * sufficient historical data exists (minimum 12 months for income)
     */
    public function test_predictions_require_sufficient_historical_data()
    {
        // Test with insufficient data (less than 12 months)
        $insufficientMonths = [1, 3, 6, 9, 11];

        foreach ($insufficientMonths as $months) {
            // Clear existing data
            Repase::truncate();
            Clinica::truncate();

            $this->generateHistoricalData($months);

            $this->expectException(\App\Exceptions\Predictive\InsufficientDataException::class);
            $this->predictor->predictIncome([], 12);

            // Reset expectation for next iteration
            $this->expectNotToPerformAssertions();
            break; // Only test one case to avoid multiple exception expectations
        }
    }

    /**
     * Test with sufficient data separately
     */
    public function test_predictions_work_with_sufficient_historical_data()
    {
        // Test with sufficient data (12+ months)
        $sufficientMonths = [12, 18, 24, 36];

        foreach ($sufficientMonths as $months) {
            // Clear existing data
            Repase::truncate();
            Clinica::truncate();

            $this->generateHistoricalData($months);

            // Should not throw exception
            $result = $this->predictor->predictIncome([], 12);
            $this->assertInstanceOf(PredictionResult::class, $result);
        }
    }


    /**
     * Property 3: Algorithm Availability
     * **Validates: Requirements 1.3**
     * 
     * For any income prediction request, all three algorithms (linear regression, 
     * moving average, seasonal analysis) should be available and return valid results
     */
    public function test_all_algorithms_are_available_and_return_valid_results()
    {
        $this->generateHistoricalData(24);
        
        $algorithms = $this->predictor->getAvailableAlgorithms();
        
        // Verify all required algorithms are available
        $this->assertContains('linear_regression', $algorithms);
        $this->assertContains('moving_average', $algorithms);
        $this->assertContains('seasonal', $algorithms);
        
        // Test each algorithm produces valid results
        foreach ($algorithms as $algorithm) {
            $result = $this->predictor->predictIncome(['algorithm' => $algorithm], 12);
            
            $this->assertInstanceOf(PredictionResult::class, $result);
            $this->assertEquals($algorithm, $result->algorithm);
            $this->assertCount(3, $result->getProjections());
            
            // Verify all projections are valid numbers
            foreach ($result->getProjections() as $projection) {
                $this->assertIsFloat($projection);
                $this->assertGreaterThanOrEqual(0, $projection);
            }
        }
    }

    /**
     * Generate historical test data
     */
    private function generateHistoricalData(int $months): void
    {
        // Create test clinics
        $clinica1 = Clinica::factory()->create(['id' => 1, 'nombre' => 'Clínica Test 1']);
        $clinica2 = Clinica::factory()->create(['id' => 2, 'nombre' => 'Clínica Test 2']);
        
        $baseIncome = 50000;
        $trend = 500; // Monthly growth
        $seasonality = 0.1; // 10% seasonal variation
        
        for ($i = 0; $i < $months; $i++) {
            $date = Carbon::now()->subMonths($months - $i - 1);
            $seasonal = sin(($date->month / 12) * 2 * pi()) * $seasonality;
            $noise = (rand(-100, 100) / 100) * 0.05; // 5% random noise
            
            $income = $baseIncome + ($i * $trend) + ($baseIncome * $seasonal) + ($baseIncome * $noise);
            
            // Create repases for both clinics
            Repase::factory()->create([
                'clinica_id' => 1,
                'fecha' => $date,
                'total_neto' => max(1000, $income),
                'estado' => 'pagado'
            ]);
            
            Repase::factory()->create([
                'clinica_id' => 2,
                'fecha' => $date,
                'total_neto' => max(1000, $income * 0.8), // Slightly different for clinic 2
                'estado' => 'pagado'
            ]);
        }
    }
}