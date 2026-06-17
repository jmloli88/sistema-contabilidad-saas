<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Registrar servicios del módulo predictivo
        $this->registerPredictiveServices();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Registra los servicios del módulo de análisis predictivo
     */
    private function registerPredictiveServices(): void
    {
        // Registrar interfaces con sus implementaciones
        $this->app->bind(
            \App\Contracts\Predictive\IncomePredictorInterface::class,
            \App\Services\Predictive\IncomePredictor::class
        );

        $this->app->bind(
            \App\Contracts\Predictive\TrendDetectorInterface::class,
            \App\Services\Predictive\TrendDetector::class
        );

        $this->app->bind(
            \App\Contracts\Predictive\ExpenseForecasterInterface::class,
            \App\Services\Predictive\ExpenseForecaster::class
        );

        $this->app->bind(
            \App\Contracts\Predictive\CapacityAnalyzerInterface::class,
            \App\Services\Predictive\CapacityAnalyzer::class
        );

        $this->app->bind(
            \App\Contracts\Predictive\ExportServiceInterface::class,
            \App\Services\Predictive\ExportService::class
        );

        $this->app->bind(
            \App\Contracts\PredictiveConfigInterface::class,
            \App\Services\Predictive\PredictiveConfig::class
        );

        $this->app->bind(
            \App\Contracts\Predictive\CacheServiceInterface::class,
            \App\Services\Predictive\CacheService::class
        );

        // Registrar servicios como singletons para optimizar rendimiento
        $this->app->singleton(\App\Services\Predictive\IncomePredictor::class);
        $this->app->singleton(\App\Services\Predictive\TrendDetector::class);
        $this->app->singleton(\App\Services\Predictive\ExpenseForecaster::class);
        $this->app->singleton(\App\Services\Predictive\CapacityAnalyzer::class);
        $this->app->singleton(\App\Services\Predictive\ExportService::class);
        $this->app->singleton(\App\Services\Predictive\PredictiveConfig::class);
        $this->app->singleton(\App\Services\Predictive\CacheService::class);
    }
}
