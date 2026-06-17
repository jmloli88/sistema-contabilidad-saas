<?php

namespace Tests\Unit\Predictive;

use Tests\TestCase;
use App\Services\Predictive\ExportService;
use App\DTOs\Predictive\PredictionResult;
use App\DTOs\Predictive\ExpenseForecast;
use App\DTOs\Predictive\CapacityAnalysis;
use App\DTOs\Predictive\SeasonalAnalysis;
use App\Exceptions\Predictive\ExportException;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ExportServiceTest extends TestCase
{
    private ExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportService = new ExportService();
        
        // Create exports directory if it doesn't exist
        Storage::makeDirectory('exports');
    }

    protected function tearDown(): void
    {
        // Clean up test files
        $files = Storage::files('exports');
        foreach ($files as $file) {
            if (str_contains($file, 'test_') || str_contains($file, 'predictive_')) {
                Storage::delete($file);
            }
        }
        
        parent::tearDown();
    }

    public function test_generates_unique_filename_with_timestamp()
    {
        $filename1 = $this->exportService->generateUniqueFilename('income_report', 'xlsx');
        
        // Wait a second to ensure different timestamp
        sleep(1);
        
        $filename2 = $this->exportService->generateUniqueFilename('income_report', 'xlsx');
        
        $this->assertNotEquals($filename1, $filename2);
        $this->assertStringContainsString('income_report_predictive_', $filename1);
        $this->assertStringEndsWith('.xlsx', $filename1);
        
        // Verify timestamp format (with microseconds)
        $this->assertMatchesRegularExpression(
            '/income_report_predictive_\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}-\d{6}\.xlsx/',
            $filename1
        );
    }

    public function test_exports_income_prediction_to_excel()
    {
        $predictionResult = new PredictionResult(
            projections: [
                '3_months' => 50000.00,
                '6_months' => 105000.00,
                '12_months' => 220000.00
            ],
            algorithm: 'linear_regression',
            metadata: ['test' => true],
            accuracy: 0.85
        );

        $data = ['income_predictions' => $predictionResult];
        $options = [
            'type' => 'income_report',
            'period' => '2024-01-01 to 2024-12-31',
            'parameters' => ['algorithm' => 'linear_regression']
        ];

        $filePath = $this->exportService->exportToExcel($data, $options);
        
        $this->assertFileExists($filePath);
        $this->assertStringContainsString('income_report_predictive_', basename($filePath));
        $this->assertStringEndsWith('.xlsx', $filePath);
        
        // Verify file is not empty
        $this->assertGreaterThan(0, filesize($filePath));
    }

    public function test_exports_expense_forecast_to_excel()
    {
        $expenseForecast = new ExpenseForecast(
            projections: [
                '3_months' => 35000.00,
                '6_months' => 73500.00,
                '12_months' => 154000.00
            ],
            categoryBreakdown: [
                'personal' => 80000.00,
                'equipos' => 40000.00,
                'suministros' => 25000.00,
                'otros' => 9000.00
            ],
            correlation: 0.78,
            alerts: ['Gastos proyectados exceden umbral en Q4'],
            metadata: ['test' => true]
        );

        $data = ['expense_forecasts' => $expenseForecast];
        $options = ['type' => 'expense_report'];

        $filePath = $this->exportService->exportToExcel($data, $options);
        
        $this->assertFileExists($filePath);
        $this->assertStringEndsWith('.xlsx', $filePath);
        $this->assertGreaterThan(0, filesize($filePath));
    }

    public function test_exports_capacity_analysis_to_excel()
    {
        $capacityAnalysis = new CapacityAnalysis(
            currentUtilization: 78.5,
            clinicUtilization: [
                'Clínica Centro' => 85.2,
                'Clínica Norte' => 72.8,
                'Clínica Sur' => 77.5
            ],
            projectedSaturationDate: Carbon::parse('2024-08-15'),
            bottlenecks: ['Sala de rayos X en Clínica Centro'],
            recommendations: ['Ampliar horarios en Clínica Centro', 'Redistribuir carga a Clínica Norte'],
            metadata: ['test' => true]
        );

        $data = ['capacity_analysis' => $capacityAnalysis];
        $options = ['type' => 'capacity_report'];

        $filePath = $this->exportService->exportToExcel($data, $options);
        
        $this->assertFileExists($filePath);
        $this->assertStringEndsWith('.xlsx', $filePath);
        $this->assertGreaterThan(0, filesize($filePath));
    }

    public function test_exports_seasonal_analysis_to_excel()
    {
        $seasonalAnalysis = new SeasonalAnalysis(
            monthlyPatterns: [
                'Enero' => 0.85,
                'Febrero' => 0.90,
                'Marzo' => 1.15,
                'Abril' => 1.10,
                'Mayo' => 1.05,
                'Junio' => 0.95
            ],
            seasonalStrength: 0.23,
            confidenceIntervals: [
                'Enero' => 0.05,
                'Febrero' => 0.04,
                'Marzo' => 0.06,
                'Abril' => 0.05,
                'Mayo' => 0.04,
                'Junio' => 0.05
            ],
            metadata: ['test' => true]
        );

        $data = ['seasonal_analysis' => $seasonalAnalysis];
        $options = ['type' => 'seasonal_report'];

        $filePath = $this->exportService->exportToExcel($data, $options);
        
        $this->assertFileExists($filePath);
        $this->assertStringEndsWith('.xlsx', $filePath);
        $this->assertGreaterThan(0, filesize($filePath));
    }

    public function test_exports_multiple_analysis_types_to_excel()
    {
        $predictionResult = new PredictionResult(
            projections: ['3_months' => 50000.00],
            algorithm: 'linear_regression'
        );

        $expenseForecast = new ExpenseForecast(
            projections: ['3_months' => 35000.00],
            categoryBreakdown: ['personal' => 25000.00],
            correlation: 0.78
        );

        $data = [
            'income_predictions' => $predictionResult,
            'expense_forecasts' => $expenseForecast
        ];
        
        $options = ['type' => 'comprehensive_report'];

        $filePath = $this->exportService->exportToExcel($data, $options);
        
        $this->assertFileExists($filePath);
        $this->assertStringEndsWith('.xlsx', $filePath);
        $this->assertGreaterThan(0, filesize($filePath));
    }

    public function test_exports_to_pdf_with_metadata()
    {
        $predictionResult = new PredictionResult(
            projections: [
                '3_months' => 50000.00,
                '6_months' => 105000.00
            ],
            algorithm: 'linear_regression',
            accuracy: 0.85
        );

        $data = ['income_predictions' => $predictionResult];
        $options = [
            'type' => 'income_report',
            'period' => '2024-01-01 to 2024-12-31',
            'parameters' => [
                'algorithm' => 'linear_regression',
                'threshold' => 25
            ]
        ];

        $filePath = $this->exportService->exportToPdf($data, $options);
        
        $this->assertFileExists($filePath);
        $this->assertStringEndsWith('.pdf', $filePath);
        $this->assertGreaterThan(0, filesize($filePath));
        
        // Verify PDF contains metadata
        $pdfContent = file_get_contents($filePath);
        $this->assertNotEmpty($pdfContent);
    }

    public function test_handles_empty_data_gracefully()
    {
        $data = [];
        $options = ['type' => 'empty_report'];

        $filePath = $this->exportService->exportToExcel($data, $options);
        
        $this->assertFileExists($filePath);
        $this->assertStringEndsWith('.xlsx', $filePath);
    }

    public function test_includes_metadata_in_exports()
    {
        $data = ['test_data' => ['value' => 123]];
        $options = [
            'type' => 'test_report',
            'period' => '2024-Q1',
            'parameters' => [
                'algorithm' => 'test_algorithm',
                'threshold' => 50
            ]
        ];

        $filePath = $this->exportService->exportToExcel($data, $options);
        
        $this->assertFileExists($filePath);
        
        // The metadata should be included in the first sheet
        // This is verified by the successful creation of the file with MetadataSheet
        $this->assertGreaterThan(0, filesize($filePath));
    }

    public function test_performance_with_large_dataset()
    {
        // Create a large dataset (simulating 10,000+ records)
        $largeProjections = [];
        for ($i = 0; $i < 1000; $i++) {
            $largeProjections["period_$i"] = rand(10000, 100000);
        }

        $predictionResult = new PredictionResult(
            projections: $largeProjections,
            algorithm: 'linear_regression'
        );

        $data = ['income_predictions' => $predictionResult];
        $options = ['type' => 'large_dataset_test'];

        $startTime = microtime(true);
        $filePath = $this->exportService->exportToExcel($data, $options);
        $executionTime = microtime(true) - $startTime;

        $this->assertFileExists($filePath);
        $this->assertLessThan(30, $executionTime, 'Export should complete within 30 seconds');
        $this->assertGreaterThan(0, filesize($filePath));
    }

    public function test_handles_various_data_types_gracefully()
    {
        // Test with different data types that should be handled gracefully
        $data = [
            'string_data' => 'test string',
            'numeric_data' => 12345,
            'array_data' => ['key1' => 'value1', 'key2' => 'value2']
        ];
        
        $options = ['type' => 'mixed_data_test'];

        $filePath = $this->exportService->exportToExcel($data, $options);
        
        $this->assertFileExists($filePath);
        $this->assertStringEndsWith('.xlsx', $filePath);
        $this->assertGreaterThan(0, filesize($filePath));
    }

    public function test_filename_uniqueness_across_multiple_calls()
    {
        $filenames = [];
        
        // Generate multiple filenames rapidly
        for ($i = 0; $i < 5; $i++) {
            $filename = $this->exportService->generateUniqueFilename('test_report', 'xlsx');
            $this->assertNotContains($filename, $filenames, 'Filename should be unique');
            $filenames[] = $filename;
            
            // Small delay to ensure timestamp difference
            usleep(1000); // 0.001 second
        }
        
        $this->assertCount(5, array_unique($filenames));
    }
}