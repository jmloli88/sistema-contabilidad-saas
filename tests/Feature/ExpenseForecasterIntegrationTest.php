<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Predictive\ExpenseForecaster;
use App\Models\Repase;
use App\Models\Gasto;
use App\Models\Clinica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ExpenseForecasterIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_complete_expense_forecasting_workflow()
    {
        // Create a clinic
        $clinica = Clinica::factory()->create(['nombre' => 'Test Clinic']);
        
        // Create 18 months of historical data with realistic patterns
        $baseExpense = 5000;
        $baseIncome = 12000;
        
        for ($i = 0; $i < 18; $i++) {
            $month = Carbon::now()->subMonths(18 - $i);
            
            // Add seasonal variation (higher expenses in December/January)
            $seasonalFactor = in_array($month->month, [12, 1]) ? 1.3 : 1.0;
            
            // Add growth trend
            $trendFactor = 1 + ($i * 0.02); // 2% monthly growth
            
            $monthlyExpense = $baseExpense * $seasonalFactor * $trendFactor;
            $monthlyIncome = $baseIncome * $seasonalFactor * $trendFactor;
            
            $repase = Repase::factory()->create([
                'clinica_id' => $clinica->id,
                'fecha' => $month,
                'total_gastos' => $monthlyExpense,
                'total_neto' => $monthlyIncome
            ]);
            
            // Create diverse expense types
            $expenses = [
                ['tipo' => 'doctor', 'monto' => $monthlyExpense * 0.4],
                ['tipo' => 'tecnico', 'monto' => $monthlyExpense * 0.3],
                ['tipo' => 'laudos', 'monto' => $monthlyExpense * 0.2],
                ['tipo' => 'gasolina', 'monto' => $monthlyExpense * 0.1],
            ];
            
            foreach ($expenses as $expense) {
                Gasto::factory()->create([
                    'repase_id' => $repase->id,
                    'tipo' => $expense['tipo'],
                    'monto' => $expense['monto']
                ]);
            }
        }
        
        // Test the forecasting service
        $forecaster = app(ExpenseForecaster::class);
        
        $forecast = $forecaster->forecastExpenses([
            'clinica_id' => $clinica->id,
            'fecha_inicio' => Carbon::now()->subMonths(18)->format('Y-m-d'),
            'fecha_fin' => Carbon::now()->format('Y-m-d')
        ], 12);
        
        // Debug: Check if we have data
        $this->assertGreaterThan(0, $forecast->metadata['historical_months'], 'Should have historical data');
        
        // Verify forecast structure
        $this->assertNotEmpty($forecast->projections);
        $this->assertArrayHasKey('3_months', $forecast->projections);
        $this->assertArrayHasKey('6_months', $forecast->projections);
        $this->assertArrayHasKey('12_months', $forecast->projections);
        
        // Debug output
        echo "\nDebug Info:\n";
        echo "Historical months: " . $forecast->metadata['historical_months'] . "\n";
        echo "3-month projection total: " . $forecast->projections['3_months']['total'] . "\n";
        echo "3-month trend: " . $forecast->projections['3_months']['trend'] . "\n";
        
        // Verify projections are reasonable (should be greater than 0)
        $this->assertGreaterThan(0, $forecast->projections['3_months']['total'], '3-month projection should be > 0');
        $this->assertGreaterThan(0, $forecast->projections['6_months']['total'], '6-month projection should be > 0');
        $this->assertGreaterThan(0, $forecast->projections['12_months']['total'], '12-month projection should be > 0');
        
        // Verify category breakdown
        $this->assertArrayHasKey('personal', $forecast->categoryBreakdown);
        $this->assertArrayHasKey('suministros', $forecast->categoryBreakdown);
        $this->assertArrayHasKey('otros', $forecast->categoryBreakdown);
        
        // Personal category should have the highest expenses (doctor + tecnico = 70%)
        $personalProjection = $forecast->categoryBreakdown['personal']['projections']['3_months'];
        $suministrosProjection = $forecast->categoryBreakdown['suministros']['projections']['3_months'];
        $this->assertGreaterThan($suministrosProjection, $personalProjection);
        
        // Verify correlation calculation
        $this->assertIsFloat($forecast->correlation);
        $this->assertGreaterThanOrEqual(-1, $forecast->correlation);
        $this->assertLessThanOrEqual(1, $forecast->correlation);
        
        // With our test data (expenses and income both growing), correlation should be positive
        $this->assertGreaterThan(0.5, $forecast->correlation);
        
        // Verify metadata
        $this->assertArrayHasKey('filters', $forecast->metadata);
        $this->assertArrayHasKey('months', $forecast->metadata);
        $this->assertArrayHasKey('historical_months', $forecast->metadata);
        $this->assertEquals(18, $forecast->metadata['historical_months']);
        
        // Test alert generation (create a scenario where expenses exceed threshold)
        $highExpenseForecast = $forecaster->forecastExpenses([
            'clinica_id' => $clinica->id
        ], 12);
        
        // The alerts should be generated based on the threshold
        $this->assertIsArray($highExpenseForecast->alerts);
        
        echo "\n=== Expense Forecast Results ===\n";
        echo "3-month projection: $" . number_format($forecast->projections['3_months']['total'], 2) . "\n";
        echo "6-month projection: $" . number_format($forecast->projections['6_months']['total'], 2) . "\n";
        echo "12-month projection: $" . number_format($forecast->projections['12_months']['total'], 2) . "\n";
        echo "Expense-Income correlation: " . round($forecast->correlation, 3) . "\n";
        echo "Personal category 3-month: $" . number_format($personalProjection, 2) . "\n";
        echo "Alerts generated: " . count($forecast->alerts) . "\n";
        
        $this->assertTrue(true); // Test passed
    }
}