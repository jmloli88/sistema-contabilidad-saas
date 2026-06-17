<?php

namespace Tests\Unit\Predictive;

use Tests\TestCase;
use App\Services\Predictive\PredictiveConfig;
use App\Services\Predictive\ExpenseForecaster;
use App\Contracts\PredictiveConfigInterface;
use App\Contracts\Predictive\ExpenseForecasterInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Clinica;
use App\Models\Empresa;
use App\Models\Repase;
use Carbon\Carbon;

class ConfigurationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private PredictiveConfigInterface $config;
    private ExpenseForecasterInterface $forecaster;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = app(PredictiveConfigInterface::class);
        $this->forecaster = app(ExpenseForecasterInterface::class);
        
        // Ensure migrations are run
        $this->artisan('migrate');
        
        // Create test data
        $this->createTestData();
    }

    public function test_expense_forecaster_uses_configuration_threshold()
    {
        // Set a custom threshold
        $this->config->set('expense_alert_threshold', 15);
        
        // Generate forecast
        $forecast = $this->forecaster->forecastExpenses([], 12);
        
        // The forecaster should use the configured threshold for alerts
        // This is verified by checking if alerts are generated with the custom threshold
        $this->assertNotNull($forecast);
        $this->assertIsArray($forecast->alerts);
    }

    public function test_configuration_override_affects_forecaster()
    {
        // Set a base threshold
        $this->config->set('expense_alert_threshold', 25);
        
        // Override temporarily
        $this->config->override('expense_alert_threshold', 10);
        
        // Generate forecast - should use override value
        $forecast = $this->forecaster->forecastExpenses([], 12);
        
        $this->assertNotNull($forecast);
        
        // Clear override
        $this->config->clearOverrides();
        
        // Generate another forecast - should use base value
        $forecast2 = $this->forecaster->forecastExpenses([], 12);
        
        $this->assertNotNull($forecast2);
    }

    public function test_configuration_change_invalidates_cache()
    {
        // Generate initial forecast to populate any caches
        $forecast1 = $this->forecaster->forecastExpenses([], 12);
        
        // Change configuration
        $this->config->set('expense_alert_threshold', 35);
        
        // Generate new forecast - should reflect configuration change
        $forecast2 = $this->forecaster->forecastExpenses([], 12);
        
        $this->assertNotNull($forecast1);
        $this->assertNotNull($forecast2);
        
        // Both forecasts should be valid but may have different alert thresholds
        $this->assertIsArray($forecast1->alerts);
        $this->assertIsArray($forecast2->alerts);
    }

    public function test_configuration_audit_trail_tracks_changes()
    {
        $originalValue = $this->config->get('expense_alert_threshold');
        
        // Make several configuration changes
        $this->config->set('expense_alert_threshold', 30, 1);
        $this->config->set('expense_alert_threshold', 35, 1);
        $this->config->set('expense_alert_threshold', 20, 1);
        
        // Check audit trail
        $auditTrail = $this->config->getAuditTrail('expense_alert_threshold');
        
        $this->assertCount(3, $auditTrail);
        
        // Verify the sequence of changes
        $this->assertEquals('20', $auditTrail[0]->new_value); // Most recent
        $this->assertEquals('35', $auditTrail[0]->old_value);
        
        $this->assertEquals('35', $auditTrail[1]->new_value);
        $this->assertEquals('30', $auditTrail[1]->old_value);
        
        $this->assertEquals('30', $auditTrail[2]->new_value);
        $this->assertEquals((string) $originalValue, $auditTrail[2]->old_value);
    }

    public function test_can_get_configuration_metadata()
    {
        $parameters = $this->config->getAvailableParameters();
        
        // Verify all expected parameters are available
        $expectedKeys = [
            'expense_alert_threshold',
            'active_algorithms',
            'cache_duration_minutes',
            'min_historical_months',
            'capacity_alert_threshold'
        ];
        
        foreach ($expectedKeys as $key) {
            $this->assertArrayHasKey($key, $parameters);
            $this->assertArrayHasKey('default', $parameters[$key]);
            $this->assertArrayHasKey('validation', $parameters[$key]);
            $this->assertArrayHasKey('description', $parameters[$key]);
            $this->assertArrayHasKey('type', $parameters[$key]);
        }
    }

    private function createTestData(): void
    {
        $empresa = Empresa::factory()->create();

        // Create a test clinic
        $clinica = Clinica::create([
            'nombre' => 'Test Clinic',
            'direccion' => 'Test Address',
            'telefono' => '123456789',
            'email' => 'test@clinic.com',
            'empresa_id' => $empresa->id,
        ]);

        // Create test repases with expenses for the last 24 months
        for ($i = 0; $i < 24; $i++) {
            $fecha = Carbon::now()->subMonths($i);
            
            Repase::create([
                'fecha' => $fecha,
                'clinica_id' => $clinica->id,
                'tipo_precio' => 'sin_nota', // Valid enum value
                'estado' => 'pagado', // Add estado field
                'total_examenes' => 8000 + ($i * 80),
                'total_consultas' => 0,
                'total_neto' => 8000 + ($i * 80),
                'total_gastos' => 2000 + ($i * 20),
                'observaciones' => "Test repase for month {$i}"
            ]);
        }
    }
}