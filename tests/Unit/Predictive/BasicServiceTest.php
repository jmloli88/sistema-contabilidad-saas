<?php

namespace Tests\Unit\Predictive;

use Tests\TestCase;
use App\Contracts\Predictive\IncomePredictorInterface;
use App\Contracts\Predictive\TrendDetectorInterface;
use App\Contracts\Predictive\ExpenseForecasterInterface;
use App\Contracts\Predictive\CapacityAnalyzerInterface;
use App\Contracts\Predictive\ExportServiceInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BasicServiceTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test que el IncomePredictor funcione básicamente
     */
    public function test_income_predictor_basic_functionality()
    {
        $predictor = app(IncomePredictorInterface::class);
        
        // Test that insufficient data throws exception
        $this->expectException(\App\Exceptions\Predictive\InsufficientDataException::class);
        $predictor->predictIncome(['clinica_id' => 1], 12);
    }

    /**
     * Test que el TrendDetector funcione básicamente
     */
    public function test_trend_detector_basic_functionality()
    {
        $detector = app(TrendDetectorInterface::class);
        
        // Test that insufficient data throws exception
        $this->expectException(\App\Exceptions\Predictive\InsufficientDataException::class);
        $detector->detectSeasonalPatterns([]);
    }

    /**
     * Test que el ExpenseForecaster funcione básicamente
     */
    public function test_expense_forecaster_basic_functionality()
    {
        $forecaster = app(ExpenseForecasterInterface::class);
        
        // Test that insufficient data throws exception
        $this->expectException(\App\Exceptions\Predictive\InsufficientDataException::class);
        $forecaster->forecastExpenses(['clinica_id' => 1], 6);
    }

    /**
     * Test que el CapacityAnalyzer funcione básicamente
     */
    public function test_capacity_analyzer_basic_functionality()
    {
        $analyzer = app(CapacityAnalyzerInterface::class);
        
        $result = $analyzer->analyzeCurrentCapacity(['clinica_id' => 1]);
        
        $this->assertNotNull($result);
        $this->assertIsFloat($result->currentUtilization);
        $this->assertIsArray($result->clinicUtilization);
        $this->assertIsArray($result->bottlenecks);
        $this->assertIsArray($result->recommendations);
    }

    /**
     * Test que el ExportService funcione básicamente
     */
    public function test_export_service_basic_functionality()
    {
        $exporter = app(ExportServiceInterface::class);
        
        $filename = $exporter->generateUniqueFilename('income_prediction', 'xlsx');
        
        $this->assertStringContainsString('income_prediction', $filename);
        $this->assertStringContainsString('predictive', $filename);
        $this->assertStringEndsWith('.xlsx', $filename);
        $this->assertMatchesRegularExpression('/\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}/', $filename);
    }

    /**
     * Test que el logging predictivo funcione
     */
    public function test_predictive_logging_works()
    {
        // Limpiar logs previos
        $logFile = storage_path('logs/predictive-' . now()->format('Y-m-d') . '.log');
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
        }

        // Escribir log de prueba
        Log::channel('predictive')->info('Test log message for predictive module');

        // Verificar que el log se escribió
        $this->assertFileExists($logFile);
        $logContent = file_get_contents($logFile);
        $this->assertStringContainsString('Test log message for predictive module', $logContent);
    }
}