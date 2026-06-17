<?php

namespace Tests\Unit\Predictive;

use Tests\TestCase;
use App\Services\Predictive\PredictiveConfig;
use App\Contracts\PredictiveConfigInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PredictiveConfigTest extends TestCase
{
    use RefreshDatabase;

    private PredictiveConfigInterface $config;

    protected function setUp(): void
    {
        parent::setUp();
        $this->config = app(PredictiveConfigInterface::class);
        
        // Ensure configuration table has default values
        $this->artisan('migrate');
    }

    public function test_can_get_configuration_value()
    {
        $threshold = $this->config->get('expense_alert_threshold');
        
        $this->assertEquals(25.0, $threshold);
    }

    public function test_can_get_configuration_with_default()
    {
        $value = $this->config->get('non_existent_key', 'default_value');
        
        $this->assertEquals('default_value', $value);
    }

    public function test_can_set_configuration_value()
    {
        $result = $this->config->set('expense_alert_threshold', 30);
        
        $this->assertTrue($result);
        $this->assertEquals(30.0, $this->config->get('expense_alert_threshold'));
    }

    public function test_validates_configuration_parameters()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Validation failed for expense_alert_threshold');
        
        $this->config->set('expense_alert_threshold', 100); // Above max of 50
    }

    public function test_validates_unknown_parameters()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown configuration parameter: unknown_key');
        
        $this->config->set('unknown_key', 'value');
    }

    public function test_can_get_all_configurations()
    {
        $all = $this->config->getAll();
        
        $this->assertIsArray($all);
        $this->assertArrayHasKey('expense_alert_threshold', $all);
        $this->assertArrayHasKey('active_algorithms', $all);
        $this->assertArrayHasKey('cache_duration_minutes', $all);
        $this->assertArrayHasKey('min_historical_months', $all);
        $this->assertArrayHasKey('capacity_alert_threshold', $all);
    }

    public function test_can_override_configuration_temporarily()
    {
        $original = $this->config->get('expense_alert_threshold');
        
        $result = $this->config->override('expense_alert_threshold', 35);
        
        $this->assertTrue($result);
        $this->assertEquals($original, $this->config->get('expense_alert_threshold'));
        $this->assertEquals(35.0, $this->config->getWithOverride('expense_alert_threshold'));
    }

    public function test_can_clear_overrides()
    {
        $this->config->override('expense_alert_threshold', 35);
        $this->assertEquals(35.0, $this->config->getWithOverride('expense_alert_threshold'));
        
        $this->config->clearOverrides();
        
        $this->assertEquals(25.0, $this->config->getWithOverride('expense_alert_threshold'));
    }

    public function test_creates_audit_trail_on_configuration_change()
    {
        $this->config->set('expense_alert_threshold', 30, 1);
        
        $auditTrail = $this->config->getAuditTrail('expense_alert_threshold');
        
        $this->assertCount(1, $auditTrail);
        $this->assertEquals('expense_alert_threshold', $auditTrail[0]->config_key);
        $this->assertEquals('25', $auditTrail[0]->old_value);
        $this->assertEquals('30', $auditTrail[0]->new_value);
        $this->assertEquals(1, $auditTrail[0]->user_id);
    }

    public function test_can_reset_to_defaults()
    {
        // Change a value
        $this->config->set('expense_alert_threshold', 40);
        $this->assertEquals(40.0, $this->config->get('expense_alert_threshold'));
        
        // Reset to default
        $result = $this->config->resetToDefaults('expense_alert_threshold');
        
        $this->assertTrue($result);
        $this->assertEquals(25.0, $this->config->get('expense_alert_threshold'));
    }

    public function test_can_reset_all_to_defaults()
    {
        // Change multiple values
        $this->config->set('expense_alert_threshold', 40);
        $this->config->set('capacity_alert_threshold', 90);
        
        // Reset all to defaults
        $result = $this->config->resetToDefaults();
        
        $this->assertTrue($result);
        $this->assertEquals(25.0, $this->config->get('expense_alert_threshold'));
        $this->assertEquals(85.0, $this->config->get('capacity_alert_threshold'));
    }

    public function test_handles_array_configuration_values()
    {
        $algorithms = ['linear_regression', 'moving_average'];
        
        $result = $this->config->set('active_algorithms', $algorithms);
        
        $this->assertTrue($result);
        $this->assertEquals($algorithms, $this->config->get('active_algorithms'));
    }

    public function test_validates_array_configuration_values()
    {
        $this->expectException(InvalidArgumentException::class);
        
        $this->config->set('active_algorithms', []); // Empty array not allowed
    }

    public function test_caches_configuration_values()
    {
        // Clear any existing cache
        Cache::flush();
        
        // First call should hit database
        $value1 = $this->config->get('expense_alert_threshold');
        
        // Second call should hit cache
        $value2 = $this->config->get('expense_alert_threshold');
        
        $this->assertEquals($value1, $value2);
        
        // Verify cache was used by checking cache directly
        $cachedValue = Cache::get('predictive_config_expense_alert_threshold');
        $this->assertNotNull($cachedValue);
    }

    public function test_clears_cache_on_configuration_change()
    {
        // Get value to populate cache
        $this->config->get('expense_alert_threshold');
        
        // Verify cache exists
        $this->assertNotNull(Cache::get('predictive_config_expense_alert_threshold'));
        
        // Change configuration
        $this->config->set('expense_alert_threshold', 30);
        
        // Verify cache was cleared
        $this->assertNull(Cache::get('predictive_config_expense_alert_threshold'));
    }

    public function test_can_get_available_parameters()
    {
        $parameters = $this->config->getAvailableParameters();
        
        $this->assertIsArray($parameters);
        $this->assertArrayHasKey('expense_alert_threshold', $parameters);
        
        $thresholdConfig = $parameters['expense_alert_threshold'];
        $this->assertArrayHasKey('default', $thresholdConfig);
        $this->assertArrayHasKey('validation', $thresholdConfig);
        $this->assertArrayHasKey('description', $thresholdConfig);
        $this->assertArrayHasKey('type', $thresholdConfig);
    }

    public function test_invalidates_prediction_cache_on_configuration_change()
    {
        // Add some dummy prediction cache entries
        DB::table('prediction_cache')->insert([
            'cache_key' => 'test_key',
            'prediction_type' => 'income',
            'filters_hash' => 'test_hash',
            'result_data' => '{}',
            'expires_at' => now()->addHour(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertEquals(1, DB::table('prediction_cache')->count());
        
        // Change configuration
        $this->config->set('expense_alert_threshold', 30);
        
        // Verify prediction cache was cleared
        $this->assertEquals(0, DB::table('prediction_cache')->count());
    }
}