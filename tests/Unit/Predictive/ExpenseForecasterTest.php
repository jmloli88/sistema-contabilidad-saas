<?php

namespace Tests\Unit\Predictive;

use Tests\TestCase;
use App\Services\Predictive\ExpenseForecaster;
use App\Contracts\Predictive\ExpenseForecasterInterface;
use App\DTOs\Predictive\ExpenseForecast;
use App\Exceptions\Predictive\InsufficientDataException;
use App\Models\Repase;
use App\Models\Gasto;
use App\Models\Clinica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ExpenseForecasterTest extends TestCase
{
    use RefreshDatabase;

    private ExpenseForecaster $forecaster;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Ensure migrations are run
        $this->artisan('migrate');
        
        // Get the service from the container (with proper dependency injection)
        $this->forecaster = app(ExpenseForecasterInterface::class);
        
        // Ensure configuration exists (may already exist from migration)
        \DB::table('prediction_configurations')->updateOrInsert(
            ['key' => 'expense_alert_threshold'],
            [
                'value' => '25',
                'description' => 'Test threshold',
                'validation_rules' => 'numeric|min:1|max:50',
                'created_at' => now(),
                'updated_at' => now()
            ]
        );
    }

    public function test_calculate_correlation_with_valid_data()
    {
        $incomes = [10000, 11000, 12000, 13000, 14000];
        $expenses = [7000, 7700, 8400, 9100, 9800];
        
        $correlation = $this->forecaster->calculateCorrelation($incomes, $expenses);
        
        // Should be a strong positive correlation
        $this->assertGreaterThan(0.9, $correlation);
        $this->assertLessThanOrEqual(1.0, $correlation);
    }

    public function test_calculate_correlation_with_empty_arrays()
    {
        $correlation = $this->forecaster->calculateCorrelation([], []);
        $this->assertEquals(0.0, $correlation);
    }

    public function test_calculate_correlation_with_mismatched_arrays()
    {
        $incomes = [10000, 11000, 12000];
        $expenses = [7000, 7700]; // Different length
        
        $correlation = $this->forecaster->calculateCorrelation($incomes, $expenses);
        $this->assertEquals(0.0, $correlation);
    }

    public function test_forecast_expenses_throws_exception_with_insufficient_data()
    {
        // Create a clinic but no repases (insufficient data)
        $clinica = Clinica::factory()->create();
        
        $this->expectException(InsufficientDataException::class);
        $this->forecaster->forecastExpenses(['clinica_id' => $clinica->id], 12);
    }

    public function test_forecast_expenses_with_sufficient_data()
    {
        $clinica = Clinica::factory()->create();
        
        // Create 12 months of historical data
        for ($i = 0; $i < 12; $i++) {
            $repase = Repase::factory()->create([
                'clinica_id' => $clinica->id,
                'fecha' => Carbon::now()->subMonths(12 - $i),
                'total_gastos' => 5000 + ($i * 100), // Increasing trend
                'total_neto' => 10000 + ($i * 200)
            ]);
            
            // Add some gastos with different categories
            Gasto::factory()->create([
                'repase_id' => $repase->id,
                'tipo' => 'doctor', // Valid enum value
                'monto' => 2000
            ]);
            
            Gasto::factory()->create([
                'repase_id' => $repase->id,
                'tipo' => 'tecnico', // Valid enum value
                'monto' => 1500
            ]);
            
            Gasto::factory()->create([
                'repase_id' => $repase->id,
                'tipo' => 'laudos', // Valid enum value
                'monto' => 1000
            ]);
        }
        
        $forecast = $this->forecaster->forecastExpenses(['clinica_id' => $clinica->id], 12);
        
        $this->assertInstanceOf(ExpenseForecast::class, $forecast);
        $this->assertNotEmpty($forecast->projections);
        $this->assertArrayHasKey('3_months', $forecast->projections);
        $this->assertArrayHasKey('6_months', $forecast->projections);
        $this->assertArrayHasKey('12_months', $forecast->projections);
        
        // Check category breakdown
        $this->assertNotEmpty($forecast->categoryBreakdown);
        $this->assertArrayHasKey('personal', $forecast->categoryBreakdown);
        $this->assertArrayHasKey('equipos', $forecast->categoryBreakdown);
        $this->assertArrayHasKey('suministros', $forecast->categoryBreakdown);
        $this->assertArrayHasKey('otros', $forecast->categoryBreakdown);
        
        // Verify that personal category has data (since we created doctor and tecnico gastos)
        $this->assertGreaterThan(0, $forecast->categoryBreakdown['personal']['historical_average']);
        
        // Check correlation is calculated
        $this->assertIsFloat($forecast->correlation);
        $this->assertGreaterThanOrEqual(-1, $forecast->correlation);
        $this->assertLessThanOrEqual(1, $forecast->correlation);
    }

    public function test_check_threshold_alerts_generates_alerts_when_exceeded()
    {
        // Create forecast with high projected expenses
        $forecast = new ExpenseForecast(
            projections: [
                '3_months' => ['total' => 10000], // High amount
                '6_months' => ['total' => 8000],  // Normal amount
            ],
            categoryBreakdown: [
                'personal' => [
                    'projections' => [
                        '3_months' => 6000, // High amount for category
                        '6_months' => 4000
                    ]
                ]
            ],
            correlation: 0.8,
            alerts: [],
            metadata: ['filters' => []]
        );
        
        // Mock some historical data by creating repases
        $clinica = Clinica::factory()->create();
        for ($i = 0; $i < 12; $i++) {
            $repase = Repase::factory()->create([
                'clinica_id' => $clinica->id,
                'fecha' => Carbon::now()->subMonths(12 - $i),
                'total_gastos' => 5000, // Average historical expense
                'total_neto' => 10000
            ]);
            
            Gasto::factory()->create([
                'repase_id' => $repase->id,
                'tipo' => 'doctor', // Valid enum value
                'monto' => 2000 // Average historical personal expense
            ]);
        }
        
        $alerts = $this->forecaster->checkThresholdAlerts($forecast);
        
        $this->assertNotEmpty($alerts);
        
        // Should have alert for total expenses exceeding threshold
        $totalAlert = collect($alerts)->firstWhere('type', 'expense_threshold_exceeded');
        $this->assertNotNull($totalAlert);
        $this->assertEquals('3_months', $totalAlert['period']);
        
        // Should have alert for personal category exceeding threshold
        $categoryAlert = collect($alerts)->firstWhere('type', 'category_threshold_exceeded');
        $this->assertNotNull($categoryAlert);
        $this->assertEquals('personal', $categoryAlert['category']);
    }
}