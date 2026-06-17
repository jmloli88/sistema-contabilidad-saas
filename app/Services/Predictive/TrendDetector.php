<?php

namespace App\Services\Predictive;

use App\Contracts\Predictive\TrendDetectorInterface;
use App\DTOs\Predictive\SeasonalAnalysis;
use App\DTOs\Predictive\ComparisonResult;
use App\Exceptions\Predictive\InsufficientDataException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TrendDetector implements TrendDetectorInterface
{
    private const MIN_SEASONAL_MONTHS = 24;

    public function detectSeasonalPatterns(array $data, int $minMonths = 24): SeasonalAnalysis
    {
        Log::channel('predictive')->info('Detecting seasonal patterns', [
            'data_points' => count($data),
            'min_months' => $minMonths
        ]);

        if (count($data) < $minMonths) {
            throw new InsufficientDataException(
                'análisis estacional',
                $minMonths,
                count($data)
            );
        }

        // Agrupar datos por mes del año
        $monthlyData = $this->groupDataByMonth($data);
        
        // Calcular patrones mensuales (porcentaje de variación respecto al promedio)
        $monthlyPatterns = $this->calculateMonthlyPatterns($monthlyData);
        
        // Calcular fuerza estacional
        $seasonalStrength = $this->calculateSeasonalStrength($monthlyPatterns);
        
        // Calcular intervalos de confianza del 95%
        $confidenceIntervals = $this->calculateConfidenceIntervals($monthlyData);

        return new SeasonalAnalysis(
            monthlyPatterns: $monthlyPatterns,
            seasonalStrength: $seasonalStrength,
            confidenceIntervals: $confidenceIntervals,
            metadata: [
                'min_months' => $minMonths,
                'data_points' => count($data),
                'analysis_date' => Carbon::now()->toISOString()
            ]
        );
    }

    public function calculateTrendStrength(array $data): float
    {
        $n = count($data);
        if ($n < 3) return 0.0;

        // Calcular regresión lineal para determinar fuerza de tendencia
        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;
        $sumY2 = 0;

        foreach ($data as $index => $point) {
            $x = $index + 1;
            $y = $point['total_ingresos'] ?? $point['value'] ?? 0;
            
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
            $sumY2 += $y * $y;
        }

        // Calcular coeficiente de correlación (R)
        $numerator = ($n * $sumXY) - ($sumX * $sumY);
        $denominator = sqrt((($n * $sumX2) - ($sumX * $sumX)) * (($n * $sumY2) - ($sumY * $sumY)));
        
        if ($denominator == 0) return 0.0;
        
        $correlation = $numerator / $denominator;
        
        // Retornar R² (coeficiente de determinación) como medida de fuerza de tendencia
        return abs($correlation * $correlation);
    }

    public function compareYearOverYear(array $currentYear, array $previousYear): ComparisonResult
    {
        $deviations = [];
        $totalCurrentYear = 0;
        $totalPreviousYear = 0;
        $significantChanges = [];

        // Comparar mes por mes
        foreach ($currentYear as $index => $currentMonth) {
            $previousMonth = $previousYear[$index] ?? null;
            
            if ($previousMonth) {
                $currentValue = $currentMonth['total_ingresos'] ?? $currentMonth['value'] ?? 0;
                $previousValue = $previousMonth['total_ingresos'] ?? $previousMonth['value'] ?? 0;
                
                $deviation = $previousValue > 0 ? (($currentValue - $previousValue) / $previousValue) * 100 : 0;
                $deviations[] = [
                    'month' => $currentMonth['month'] ?? "Mes " . ($index + 1),
                    'current' => $currentValue,
                    'previous' => $previousValue,
                    'deviation_percent' => $deviation
                ];

                // Identificar cambios significativos (>15% de variación)
                if (abs($deviation) > 15) {
                    $significantChanges[] = [
                        'month' => $currentMonth['month'] ?? "Mes " . ($index + 1),
                        'deviation_percent' => $deviation,
                        'type' => $deviation > 0 ? 'increase' : 'decrease'
                    ];
                }

                $totalCurrentYear += $currentValue;
                $totalPreviousYear += $previousValue;
            }
        }

        // Calcular cambio general año sobre año
        $overallChange = $totalPreviousYear > 0 ? 
            (($totalCurrentYear - $totalPreviousYear) / $totalPreviousYear) * 100 : 0;

        return new ComparisonResult(
            deviations: $deviations,
            overallChange: $overallChange,
            significantChanges: $significantChanges,
            metadata: [
                'total_current_year' => $totalCurrentYear,
                'total_previous_year' => $totalPreviousYear,
                'comparison_date' => Carbon::now()->toISOString()
            ]
        );
    }

    private function groupDataByMonth(array $data): array
    {
        $monthlyData = [];
        
        foreach ($data as $point) {
            $month = $this->extractMonth($point);
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = [];
            }
            // Buscar el valor en diferentes claves posibles
            $value = $point['income'] ?? $point['total_ingresos'] ?? $point['value'] ?? 0;
            $monthlyData[$month][] = $value;
        }

        return $monthlyData;
    }

    private function extractMonth(array $point): int
    {
        if (isset($point['period'])) {
            // Formato YYYY-MM
            return (int) date('n', strtotime($point['period'] . '-01'));
        }
        
        if (isset($point['month'])) {
            // Formato YYYY-MM
            return (int) date('n', strtotime($point['month'] . '-01'));
        }
        
        if (isset($point['date'])) {
            return (int) date('n', strtotime($point['date']));
        }

        // Fallback: asumir enero
        return 1;
    }

    private function calculateMonthlyPatterns(array $monthlyData): array
    {
        // Calcular promedio general
        $allValues = [];
        foreach ($monthlyData as $values) {
            $allValues = array_merge($allValues, $values);
        }
        $overallAverage = count($allValues) > 0 ? array_sum($allValues) / count($allValues) : 0;

        $patterns = [];
        for ($month = 1; $month <= 12; $month++) {
            if (isset($monthlyData[$month]) && count($monthlyData[$month]) > 0) {
                $monthAverage = array_sum($monthlyData[$month]) / count($monthlyData[$month]);
                $variationPercent = $overallAverage > 0 ? 
                    (($monthAverage - $overallAverage) / $overallAverage) * 100 : 0;
                
                $patterns[$month] = [
                    'month_name' => $this->getMonthName($month),
                    'average_value' => (float) $monthAverage,
                    'variation_percent' => (float) $variationPercent,
                    'data_points' => count($monthlyData[$month])
                ];
            } else {
                $patterns[$month] = [
                    'month_name' => $this->getMonthName($month),
                    'average_value' => (float) $overallAverage,
                    'variation_percent' => 0.0,
                    'data_points' => 0
                ];
            }
        }

        return $patterns;
    }

    private function calculateSeasonalStrength(array $monthlyPatterns): float
    {
        $variations = array_column($monthlyPatterns, 'variation_percent');
        
        if (empty($variations)) return 0.0;

        // Calcular desviación estándar de las variaciones mensuales
        $mean = array_sum($variations) / count($variations);
        $squaredDiffs = array_map(fn($x) => pow($x - $mean, 2), $variations);
        $variance = array_sum($squaredDiffs) / count($squaredDiffs);
        
        return sqrt($variance); // Desviación estándar como medida de fuerza estacional
    }

    private function calculateConfidenceIntervals(array $monthlyData): array
    {
        $intervals = [];
        
        foreach ($monthlyData as $month => $values) {
            if (count($values) < 2) {
                $intervals[$month] = [
                    'lower_bound' => 0,
                    'upper_bound' => 0,
                    'confidence_level' => 95
                ];
                continue;
            }

            $mean = array_sum($values) / count($values);
            $variance = $this->calculateVariance($values, $mean);
            $standardError = sqrt($variance / count($values));
            
            // Usar distribución t para intervalos de confianza del 95%
            $tValue = $this->getTValue(count($values) - 1, 0.05); // 95% confidence
            $marginOfError = $tValue * $standardError;
            
            $intervals[$month] = [
                'lower_bound' => max(0, $mean - $marginOfError),
                'upper_bound' => $mean + $marginOfError,
                'confidence_level' => 95
            ];
        }

        return $intervals;
    }

    private function calculateVariance(array $values, float $mean): float
    {
        $squaredDiffs = array_map(fn($x) => pow($x - $mean, 2), $values);
        return array_sum($squaredDiffs) / (count($squaredDiffs) - 1); // Sample variance
    }

    private function getTValue(int $degreesOfFreedom, float $alpha): float
    {
        // Aproximación simple para t-value del 95% de confianza
        // Para una implementación más precisa, se usaría una tabla t o librería estadística
        if ($degreesOfFreedom >= 30) return 1.96; // Aproximación normal
        if ($degreesOfFreedom >= 20) return 2.086;
        if ($degreesOfFreedom >= 10) return 2.228;
        if ($degreesOfFreedom >= 5) return 2.571;
        return 3.182; // Para df muy pequeños
    }

    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        
        return $months[$month] ?? 'Desconocido';
    }
}