<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Clinica;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictiveControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test users
        $this->adminUser = User::factory()->create(['role' => 'administrador']);
        $this->regularUser = User::factory()->create(['role' => 'usuario']);
        
        // Create subscriptions so users pass through subscription middleware
        $this->adminUser->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_admin_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
        $this->regularUser->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_regular_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
        
        // Create test clinics
        Clinica::factory()->count(3)->create();
    }

    public function test_dashboard_requires_admin_access()
    {
        // Test unauthenticated access
        $response = $this->get(route('predictivo.dashboard'));
        $response->assertRedirect(route('login'));

        // Test regular user access
        $response = $this->actingAs($this->regularUser)
                        ->get(route('predictivo.dashboard'));
        $response->assertStatus(403);

        // Test admin access
        $response = $this->actingAs($this->adminUser)
                        ->get(route('predictivo.dashboard'));
        $response->assertStatus(200);
    }

    public function test_dashboard_loads_successfully_for_admin()
    {
        $response = $this->actingAs($this->adminUser)
                        ->get(route('predictivo.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('predictive.dashboard');
        $response->assertViewHas(['filters', 'clinicas']);
    }

    public function test_income_projection_view_loads()
    {
        $response = $this->actingAs($this->adminUser)
                        ->get(route('predictivo.ingresos'));

        $response->assertStatus(200);
        $response->assertViewIs('predictive.income-projection');
    }

    public function test_expense_forecast_view_loads()
    {
        $response = $this->actingAs($this->adminUser)
                        ->get(route('predictivo.gastos'));

        $response->assertStatus(200);
        $response->assertViewIs('predictive.expense-forecast');
    }

    public function test_capacity_analysis_view_loads()
    {
        $response = $this->actingAs($this->adminUser)
                        ->get(route('predictivo.capacidad'));

        $response->assertStatus(200);
        $response->assertViewIs('predictive.capacity-analysis');
    }

    public function test_trend_analysis_view_loads()
    {
        $response = $this->actingAs($this->adminUser)
                        ->get(route('predictivo.tendencias'));

        $response->assertStatus(200);
        $response->assertViewIs('predictive.trend-analysis');
    }

    public function test_dashboard_handles_filters()
    {
        $clinica = Clinica::first();
        
        $response = $this->actingAs($this->adminUser)
                        ->get(route('predictivo.dashboard', [
                            'clinica_id' => $clinica->id,
                            'fecha_desde' => '2024-01-01',
                            'fecha_hasta' => '2024-12-31'
                        ]));

        $response->assertStatus(200);
        $response->assertViewHas('filters', function ($filters) use ($clinica) {
            return $filters['clinica_id'] == $clinica->id &&
                   $filters['fecha_desde'] == '2024-01-01' &&
                   $filters['fecha_hasta'] == '2024-12-31';
        });
    }

    public function test_views_handle_insufficient_data_gracefully()
    {
        // Test with no historical data - should show error messages gracefully
        $response = $this->actingAs($this->adminUser)
                        ->get(route('predictivo.ingresos'));

        $response->assertStatus(200);
        // Should either show error or handle gracefully with empty data
    }
}