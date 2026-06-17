<?php

namespace App\Services\Predictive;

use App\Contracts\Predictive\ExportServiceInterface;
use App\DTOs\Predictive\PredictionResult;
use App\DTOs\Predictive\ExpenseForecast;
use App\DTOs\Predictive\CapacityAnalysis;
use App\DTOs\Predictive\SeasonalAnalysis;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Carbon\Carbon;

class ExportService implements ExportServiceInterface
{
    public function exportToExcel(array $data, array $options = []): string
    {
        $startTime = microtime(true);
        
        Log::channel('predictive')->info('Starting Excel export', [
            'data_size' => count($data),
            'options' => $options
        ]);

        try {
            $filename = $this->generateUniqueFilename($options['type'] ?? 'predictive_report', 'xlsx');
            $filePath = 'exports/' . $filename;
            
            // Create Excel export with multiple sheets
            $export = new PredictiveExcelExport($data, $options);
            
            Excel::store($export, $filePath, 'local');
            
            $executionTime = microtime(true) - $startTime;
            
            Log::channel('predictive')->info('Excel export completed', [
                'filename' => $filename,
                'execution_time' => $executionTime,
                'file_size' => Storage::size($filePath)
            ]);
            
            // Ensure performance requirement: < 30 seconds for 10,000+ records
            if ($executionTime > 30) {
                Log::channel('predictive')->warning('Excel export exceeded performance threshold', [
                    'execution_time' => $executionTime,
                    'threshold' => 30
                ]);
            }
            
            return Storage::path($filePath);
            
        } catch (\Exception $e) {
            Log::channel('predictive')->error('Excel export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \App\Exceptions\Predictive\ExportException(
                "Failed to export to Excel: " . $e->getMessage()
            );
        }
    }

    public function exportToPdf(array $data, array $options = []): string
    {
        $startTime = microtime(true);
        
        Log::channel('predictive')->info('Starting PDF export', [
            'data_size' => count($data),
            'options' => $options
        ]);

        try {
            $filename = $this->generateUniqueFilename($options['type'] ?? 'predictive_report', 'pdf');
            $filePath = 'exports/' . $filename;
            
            // Prepare data for PDF view
            $pdfData = $this->preparePdfData($data, $options);
            
            // Generate PDF with charts and professional formatting
            $pdf = Pdf::loadView('predictive.exports.pdf-report', $pdfData)
                ->setPaper('a4', 'portrait')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isPhpEnabled' => true,
                    'defaultFont' => 'Arial',
                    'dpi' => 150
                ]);
            
            Storage::put($filePath, $pdf->output());
            
            $executionTime = microtime(true) - $startTime;
            
            Log::channel('predictive')->info('PDF export completed', [
                'filename' => $filename,
                'execution_time' => $executionTime,
                'file_size' => Storage::size($filePath)
            ]);
            
            // Ensure performance requirement: < 30 seconds for 10,000+ records
            if ($executionTime > 30) {
                Log::channel('predictive')->warning('PDF export exceeded performance threshold', [
                    'execution_time' => $executionTime,
                    'threshold' => 30
                ]);
            }
            
            return Storage::path($filePath);
            
        } catch (\Exception $e) {
            Log::channel('predictive')->error('PDF export failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \App\Exceptions\Predictive\ExportException(
                "Failed to export to PDF: " . $e->getMessage()
            );
        }
    }

    public function generateUniqueFilename(string $type, string $format): string
    {
        return sprintf(
            '%s_%s_%s.%s',
            $type,
            'predictive',
            now()->format('Y-m-d_H-i-s-u'), // Added microseconds for uniqueness
            $format
        );
    }

    /**
     * Prepare data for PDF export with metadata and formatting
     */
    private function preparePdfData(array $data, array $options): array
    {
        $metadata = [
            'generation_date' => now()->format('Y-m-d H:i:s'),
            'period_analyzed' => $options['period'] ?? 'N/A',
            'parameters' => $options['parameters'] ?? [],
            'report_type' => $options['type'] ?? 'General Report',
            'total_records' => count($data)
        ];

        return [
            'data' => $data,
            'metadata' => $metadata,
            'options' => $options,
            'charts' => $this->generateChartData($data, $options)
        ];
    }

    /**
     * Generate chart data for PDF visualization
     */
    private function generateChartData(array $data, array $options): array
    {
        $charts = [];
        
        foreach ($data as $analysisType => $analysisData) {
            switch ($analysisType) {
                case 'income_predictions':
                    $charts['income'] = $this->generateIncomeChartData($analysisData);
                    break;
                case 'expense_forecasts':
                    $charts['expenses'] = $this->generateExpenseChartData($analysisData);
                    break;
                case 'capacity_analysis':
                    $charts['capacity'] = $this->generateCapacityChartData($analysisData);
                    break;
                case 'seasonal_analysis':
                    $charts['seasonal'] = $this->generateSeasonalChartData($analysisData);
                    break;
            }
        }
        
        return $charts;
    }

    private function generateIncomeChartData($data): array
    {
        if (!$data instanceof PredictionResult) {
            return [];
        }

        return [
            'type' => 'line',
            'labels' => array_keys($data->getProjections()),
            'values' => array_values($data->getProjections()),
            'title' => 'Proyección de Ingresos'
        ];
    }

    private function generateExpenseChartData($data): array
    {
        if (!$data instanceof ExpenseForecast) {
            return [];
        }

        return [
            'type' => 'bar',
            'labels' => array_keys($data->categoryBreakdown),
            'values' => array_values($data->categoryBreakdown),
            'title' => 'Pronóstico de Gastos por Categoría'
        ];
    }

    private function generateCapacityChartData($data): array
    {
        if (!$data instanceof CapacityAnalysis) {
            return [];
        }

        return [
            'type' => 'pie',
            'labels' => array_keys($data->clinicUtilization),
            'values' => array_values($data->clinicUtilization),
            'title' => 'Utilización por Clínica'
        ];
    }

    private function generateSeasonalChartData($data): array
    {
        if (!$data instanceof SeasonalAnalysis) {
            return [];
        }

        return [
            'type' => 'line',
            'labels' => array_keys($data->monthlyPatterns),
            'values' => array_values($data->monthlyPatterns),
            'title' => 'Patrones Estacionales'
        ];
    }
}

/**
 * Excel export class with multiple sheets support
 */
class PredictiveExcelExport implements WithMultipleSheets
{
    private array $data;
    private array $options;

    public function __construct(array $data, array $options = [])
    {
        $this->data = $data;
        $this->options = $options;
    }

    public function sheets(): array
    {
        $sheets = [];
        
        // Metadata sheet
        $sheets[] = new MetadataSheet($this->options);
        
        // Analysis sheets based on data type
        foreach ($this->data as $analysisType => $analysisData) {
            switch ($analysisType) {
                case 'income_predictions':
                    $sheets[] = new IncomePredictionSheet($analysisData);
                    break;
                case 'expense_forecasts':
                    $sheets[] = new ExpenseForecastSheet($analysisData);
                    break;
                case 'capacity_analysis':
                    $sheets[] = new CapacityAnalysisSheet($analysisData);
                    break;
                case 'seasonal_analysis':
                    $sheets[] = new SeasonalAnalysisSheet($analysisData);
                    break;
                default:
                    $sheets[] = new GenericDataSheet($analysisData, $analysisType);
            }
        }
        
        return $sheets;
    }
}

/**
 * Metadata sheet for Excel export
 */
class MetadataSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
    private array $options;

    public function __construct(array $options)
    {
        $this->options = $options;
    }

    public function array(): array
    {
        return [
            ['Fecha de Generación', now()->format('Y-m-d H:i:s')],
            ['Período Analizado', $this->options['period'] ?? 'N/A'],
            ['Tipo de Reporte', $this->options['type'] ?? 'Reporte General'],
            ['Parámetros', json_encode($this->options['parameters'] ?? [])],
            ['Total de Registros', count($this->options['data'] ?? [])],
            ['Versión del Sistema', '1.0.0'],
        ];
    }

    public function title(): string
    {
        return 'Metadatos';
    }

    public function headings(): array
    {
        return ['Campo', 'Valor'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'A:B' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_LEFT]],
        ];
    }
}

/**
 * Income prediction sheet
 */
class IncomePredictionSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        if (!$this->data instanceof PredictionResult) {
            return [];
        }

        $rows = [];
        foreach ($this->data->getProjections() as $period => $value) {
            $rows[] = [$period, number_format($value, 2), $this->data->algorithm];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Predicción de Ingresos';
    }

    public function headings(): array
    {
        return ['Período', 'Proyección (€)', 'Algoritmo'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'B:B' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
        ];
    }
}

/**
 * Expense forecast sheet
 */
class ExpenseForecastSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        if (!$this->data instanceof ExpenseForecast) {
            return [];
        }

        $rows = [];
        
        // Projections
        foreach ($this->data->projections as $period => $value) {
            $rows[] = ['Proyección', $period, number_format($value, 2), ''];
        }
        
        // Category breakdown
        foreach ($this->data->categoryBreakdown as $category => $value) {
            $rows[] = ['Categoría', $category, number_format($value, 2), ''];
        }
        
        // Correlation
        $rows[] = ['Correlación', 'Ingresos-Gastos', number_format($this->data->correlation, 4), ''];

        return $rows;
    }

    public function title(): string
    {
        return 'Pronóstico de Gastos';
    }

    public function headings(): array
    {
        return ['Tipo', 'Descripción', 'Valor (€)', 'Notas'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'C:C' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
        ];
    }
}

/**
 * Capacity analysis sheet
 */
class CapacityAnalysisSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        if (!$this->data instanceof CapacityAnalysis) {
            return [];
        }

        $rows = [];
        
        // Current utilization
        $rows[] = ['Utilización General', number_format($this->data->currentUtilization, 2) . '%', '', ''];
        
        // Clinic utilization
        foreach ($this->data->clinicUtilization as $clinic => $utilization) {
            $rows[] = ['Clínica', $clinic, number_format($utilization, 2) . '%', ''];
        }
        
        // Saturation date
        if ($this->data->projectedSaturationDate) {
            $rows[] = ['Fecha de Saturación', $this->data->projectedSaturationDate->format('Y-m-d'), '', ''];
        }
        
        // Bottlenecks
        foreach ($this->data->bottlenecks as $bottleneck) {
            $rows[] = ['Cuello de Botella', $bottleneck, '', 'Requiere atención'];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Análisis de Capacidad';
    }

    public function headings(): array
    {
        return ['Tipo', 'Descripción', 'Valor', 'Observaciones'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'C:C' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
        ];
    }
}

/**
 * Seasonal analysis sheet
 */
class SeasonalAnalysisSheet implements FromArray, WithTitle, WithHeadings, WithStyles
{
    private $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        if (!$this->data instanceof SeasonalAnalysis) {
            return [];
        }

        $rows = [];
        
        // Monthly patterns
        foreach ($this->data->monthlyPatterns as $month => $pattern) {
            $confidence = $this->data->confidenceIntervals[$month] ?? null;
            $rows[] = [
                $month, 
                number_format($pattern, 4), 
                $confidence ? number_format($confidence, 4) : 'N/A'
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Análisis Estacional';
    }

    public function headings(): array
    {
        return ['Mes', 'Patrón', 'Intervalo de Confianza'];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
            'B:C' => ['alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT]],
        ];
    }
}

/**
 * Generic data sheet for unknown data types
 */
class GenericDataSheet implements FromArray, WithTitle, WithHeadings
{
    private $data;
    private string $sheetName;

    public function __construct($data, string $sheetName)
    {
        $this->data = $data;
        $this->sheetName = $sheetName;
    }

    public function array(): array
    {
        if (is_array($this->data)) {
            // Ensure all elements are arrays
            $result = [];
            foreach ($this->data as $key => $value) {
                if (is_array($value)) {
                    $result[] = $value;
                } else {
                    $result[] = [$key, $value];
                }
            }
            return $result;
        }
        
        if (is_object($this->data) && method_exists($this->data, 'toArray')) {
            $arrayData = $this->data->toArray();
            $result = [];
            foreach ($arrayData as $key => $value) {
                if (is_array($value)) {
                    $result[] = array_merge([$key], array_values($value));
                } else {
                    $result[] = [$key, $value];
                }
            }
            return $result;
        }
        
        // Handle primitive types
        if (is_scalar($this->data)) {
            return [['Value', $this->data]];
        }
        
        return [['No data available', '']];
    }

    public function title(): string
    {
        return ucfirst(str_replace('_', ' ', $this->sheetName));
    }

    public function headings(): array
    {
        if (is_array($this->data) && !empty($this->data)) {
            $firstRow = reset($this->data);
            if (is_array($firstRow)) {
                return array_keys($firstRow);
            }
        }
        
        return ['Key', 'Value'];
    }
}