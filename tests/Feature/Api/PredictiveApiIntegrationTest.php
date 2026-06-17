<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Models\Clinica;
use App\Models\Repase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictiveApiIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_controller_can_be_instantiated(): void
    {
        $controller = app(\App\Http\Controllers\Api\PredictiveApiController::class);
        $this->assertInstanceOf(\App\Http\Controllers\Api\PredictiveApiController::class, $controller);
    }

    public function test_api_routes_are_registered(): void
    {
        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes())->map(function ($route) {
            return $route->uri();
        });

        $this->assertTrue($routes->contains('api/predictivo/ingresos/{months}'));
        $this->assertTrue($routes->contains('api/predictivo/gastos/{months}'));
        $this->assertTrue($routes->contains('api/predictivo/capacidad/actual'));
        $this->assertTrue($routes->contains('api/predictivo/tendencias/estacionales'));
        $this->assertTrue($routes->contains('api/predictivo/configuracion'));
    }

    public function test_services_are_properly_injected(): void
    {
        $controller = app(\App\Http\Controllers\Api\PredictiveApiController::class);
        
        // Use reflection to check if services are injected
        $reflection = new \ReflectionClass($controller);
        $constructor = $reflection->getConstructor();
        $parameters = $constructor->getParameters();
        
        $expectedServices = [
            'incomePredictor',
            'trendDetector', 
            'expenseForecaster',
            'capacityAnalyzer',
            'config'
        ];
        
        $this->assertCount(5, $parameters);
        
        foreach ($parameters as $index => $parameter) {
            $this->assertEquals($expectedServices[$index], $parameter->getName());
        }
    }
}