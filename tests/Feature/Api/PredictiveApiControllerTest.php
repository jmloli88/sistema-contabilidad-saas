<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Clinica;
use App\Models\Repase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictiveApiControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private Clinica $clinica;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'role' => 'administrador'
        ]);
        
        // Create test clinic
        $this->clinica = Clinica::factory()->create();
        
        // Create some test data
        $this->createTestData();
    }

    private function createTestData(): void
    {
        // Create 24 months of test repases
        for ($i = 0; $i < 24; $i++) {
            Repase::factory()->create([
                'clinica_id' => $this->clinica->id,
                'fecha' => now()->subMonths($i),
                'total_neto' => 50000 + ($i * 1000) + rand(-5000, 5000)
            ]);
        }
    }

    public function test_get_income_projection_returns_valid_response(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/predictivo/ingresos/12?clinica_id={$this->clinica->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'projections' => [
                        '3_months',
                        '6_months', 
                        '12_months'
                    ],
                    'algorithms',
                    'confidence',
                    'trend',
                    'chart_data'
                ],
                'meta' => [
                    'generated_at',
                    'filters_applied',
                    'months_requested'
                ]
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_get_expense_forecast_returns_valid_response(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/predictivo/gastos/6?clinica_id={$this->clinica->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'forecast' => [
                        '3_months',
                        '6_months',
                        '12_months'
                    ],
                    'by_category',
                    'alerts',
                    'correlation_with_income',
                    'chart_data'
                ],
                'meta'
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_get_current_capacity_returns_valid_response(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/predictivo/capacidad/actual?clinica_id={$this->clinica->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'current_utilization',
                    'utilization_by_clinic',
                    'saturation_date',
                    'days_to_saturation',
                    'bottlenecks',
                    'recommendations',
                    'growth_rate',
                    'chart_data'
                ],
                'meta'
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_get_seasonal_trends_returns_valid_response(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/predictivo/tendencias/estacionales?clinica_id={$this->clinica->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'seasonal_patterns',
                    'trend_strength',
                    'peak_months',
                    'low_months',
                    'seasonal_strength',
                    'trend_direction',
                    'confidence_intervals',
                    'chart_data'
                ],
                'meta'
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_update_configuration_returns_valid_response(): void
    {
        $configData = [
            'expense_alert_threshold' => 30,
            'capacity_alert_threshold' => 80,
            'active_algorithms' => ['linear_regression', 'moving_average'],
            'cache_duration_minutes' => 120
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/predictivo/configuracion', $configData);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'updated_settings',
                    'current_config'
                ],
                'meta'
            ]);

        $this->assertTrue($response->json('success'));
    }

    public function test_unauthorized_access_returns_401(): void
    {
        $response = $this->getJson('/api/predictivo/ingresos/12');
        $response->assertStatus(401);
    }

    public function test_non_admin_access_returns_403(): void
    {
        $regularUser = User::factory()->create(['role' => 'usuario']);
        
        $response = $this->actingAs($regularUser)
            ->getJson('/api/predictivo/ingresos/12');
            
        $response->assertStatus(403);
    }

    public function test_invalid_months_parameter_returns_422(): void
    {
        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/predictivo/ingresos/invalid');

        $response->assertStatus(404); // Route not found for invalid parameter
    }

    public function test_insufficient_data_returns_error(): void
    {
        // Clear all repases to simulate insufficient data
        Repase::truncate();

        $response = $this->actingAs($this->adminUser, 'sanctum')
            ->getJson('/api/predictivo/tendencias/estacionales?min_months=24');

        $response->assertStatus(400)
            ->assertJson([
                'success' => false
            ]);
    }
}