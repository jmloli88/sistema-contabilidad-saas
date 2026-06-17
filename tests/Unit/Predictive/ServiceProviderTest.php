<?php

namespace Tests\Unit\Predictive;

use Tests\TestCase;
use App\Contracts\Predictive\IncomePredictorInterface;
use App\Contracts\Predictive\TrendDetectorInterface;
use App\Contracts\Predictive\ExpenseForecasterInterface;
use App\Contracts\Predictive\CapacityAnalyzerInterface;
use App\Contracts\Predictive\ExportServiceInterface;
use App\Services\Predictive\IncomePredictor;
use App\Services\Predictive\TrendDetector;
use App\Services\Predictive\ExpenseForecaster;
use App\Services\Predictive\CapacityAnalyzer;
use App\Services\Predictive\ExportService;

class ServiceProviderTest extends TestCase
{
    /**
     * Test que todos los servicios predictivos se registren correctamente
     */
    public function test_all_predictive_services_are_registered()
    {
        // Verificar que las interfaces se resuelven a las implementaciones correctas
        $this->assertInstanceOf(
            IncomePredictor::class,
            $this->app->make(IncomePredictorInterface::class)
        );

        $this->assertInstanceOf(
            TrendDetector::class,
            $this->app->make(TrendDetectorInterface::class)
        );

        $this->assertInstanceOf(
            ExpenseForecaster::class,
            $this->app->make(ExpenseForecasterInterface::class)
        );

        $this->assertInstanceOf(
            CapacityAnalyzer::class,
            $this->app->make(CapacityAnalyzerInterface::class)
        );

        $this->assertInstanceOf(
            ExportService::class,
            $this->app->make(ExportServiceInterface::class)
        );
    }

    /**
     * Test que los servicios se registren como singletons
     */
    public function test_services_are_singletons()
    {
        $service1 = $this->app->make(IncomePredictorInterface::class);
        $service2 = $this->app->make(IncomePredictorInterface::class);

        $this->assertSame($service1, $service2);
    }

    /**
     * Test que el canal de logging predictivo esté configurado
     */
    public function test_predictive_logging_channel_is_configured()
    {
        $channels = config('logging.channels');
        
        $this->assertArrayHasKey('predictive', $channels);
        $this->assertEquals('daily', $channels['predictive']['driver']);
        $this->assertStringContainsString('predictive.log', $channels['predictive']['path']);
    }
}