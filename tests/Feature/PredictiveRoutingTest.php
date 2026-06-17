<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictiveRoutingTest extends TestCase
{
    use RefreshDatabase;

    private User $adminUser;
    private User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create admin user
        $this->adminUser = User::factory()->create([
            'role' => 'administrador',
            'email' => 'admin@test.com'
        ]);
        
        // Create regular user
        $this->regularUser = User::factory()->create([
            'role' => 'usuario',
            'email' => 'user@test.com'
        ]);
        
        // Create subscriptions so users pass through subscription middleware
        $this->adminUser->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_admin_route',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
        $this->regularUser->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_regular_route',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
    }

    public function test_web_routes_require_authentication()
    {
        $routes = [
            '/predictivo',
            '/predictivo/ingresos',
            '/predictivo/gastos',
            '/predictivo/capacidad',
            '/predictivo/tendencias'
        ];

        foreach ($routes as $route) {
            $response = $this->get($route);
            $response->assertRedirect('/login');
        }
    }

    public function test_web_routes_require_admin_role()
    {
        $routes = [
            '/predictivo',
            '/predictivo/ingresos',
            '/predictivo/gastos',
            '/predictivo/capacidad',
            '/predictivo/tendencias'
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->regularUser)->get($route);
            $response->assertStatus(403);
        }
    }

    public function test_web_routes_accessible_by_admin()
    {
        $routes = [
            '/predictivo',
            '/predictivo/ingresos',
            '/predictivo/gastos',
            '/predictivo/capacidad',
            '/predictivo/tendencias'
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->adminUser)->get($route);
            // Routes should be accessible (not 401/403), even if they return 500 due to missing data
            $this->assertNotEquals(401, $response->status(), "Route $route should not return 401");
            $this->assertNotEquals(403, $response->status(), "Route $route should not return 403");
        }
    }

    public function test_api_routes_require_authentication()
    {
        $routes = [
            '/api/predictivo/ingresos/3',
            '/api/predictivo/gastos/6',
            '/api/predictivo/capacidad/actual',
            '/api/predictivo/tendencias/estacionales'
        ];

        foreach ($routes as $route) {
            $response = $this->getJson($route);
            $response->assertStatus(401);
        }
    }

    public function test_api_routes_require_admin_role()
    {
        $routes = [
            '/api/predictivo/ingresos/3',
            '/api/predictivo/gastos/6',
            '/api/predictivo/capacidad/actual',
            '/api/predictivo/tendencias/estacionales'
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->regularUser)->getJson($route);
            $response->assertStatus(403);
        }
    }

    public function test_api_routes_accessible_by_admin()
    {
        $routes = [
            '/api/predictivo/ingresos/3',
            '/api/predictivo/gastos/6',
            '/api/predictivo/capacidad/actual',
            '/api/predictivo/tendencias/estacionales'
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->adminUser)->getJson($route);
            // Should not return auth errors (401/403), even if functionality isn't complete
            $this->assertNotEquals(401, $response->status(), "Route $route should not return 401");
            $this->assertNotEquals(403, $response->status(), "Route $route should not return 403");
        }
    }

    public function test_api_rate_limiting_is_applied()
    {
        $route = '/api/predictivo/ingresos/3';
        
        // Make 61 requests (exceeding the 60 per minute limit)
        for ($i = 0; $i < 61; $i++) {
            $response = $this->actingAs($this->adminUser)->getJson($route);
            
            if ($i < 60) {
                // First 60 requests should be allowed (or return validation errors)
                $this->assertNotEquals(429, $response->status(), "Request $i should not be rate limited");
            } else {
                // 61st request should be rate limited
                $response->assertStatus(429);
                break;
            }
        }
    }

    public function test_configuration_update_route_works()
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/predictivo/configuracion', [
                'expense_alert_threshold' => 30,
                'active_algorithms' => ['linear_regression', 'moving_average']
            ]);

        // Should not return auth errors (401/403), even if functionality isn't complete
        $this->assertNotEquals(401, $response->status(), "Configuration route should not return 401");
        $this->assertNotEquals(403, $response->status(), "Configuration route should not return 403");
    }
}