<?php

namespace App\Services\Predictive;

use App\Contracts\Predictive\ExpenseForecasterInterface;
use App\Contracts\Predictive\CacheServiceInterface;
use App\Contracts\PredictiveConfigInterface;
use App\DTOs\Predictive\ExpenseForecast;
use App\Models\Gasto;
use App\Models\Repase;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Exceptions\Predictive\InsufficientDataException;

class ExpenseForecaster implements ExpenseForecasterInterface
{
    private const EXPENSE_CATEGORIES = ['personal', 'equipos', 'suministros', 'otros'];
    private const MIN_HISTORICAL_MONTHS = 12;

    public function __construct(
        private PredictiveConfigInterface $config,
        private CacheServiceInterface $cacheService
    ) {}

    public function forecastExpenses(array $filters, int $months): ExpenseForecast
    {
        Log::channel('predictive')->info('Forecasting expenses', [
            'filters' => $filters,
            'months' => $months
        ]);

        // Try to get cached result first
        $cachedResult = $this->cacheService->getCachedPrediction('expense', $filters);
        if ($cachedResult !== null) {
            Log::channel('predictive')->info('Returning cached expense forecast', [
                'filters' => $filters
            ]);
            return $this->deserializeExpenseForecast($cachedResult);
        }

        try {
            // Generate new forecast
            $forecast = $this->generateForecast($filters, $months);
            
            // Cache the result
            $this->cacheService->cachePrediction('expense', $filters, $this->serializeExpenseForecast($forecast));
            
            return $forecast;
            
        } catch (\Exception $e) {
            // Try fallback from cache service
            $fallbackResult = $this->cacheService->getFallbackResult('expense', $filters, $e);
            
            if ($fallbackResult !== null) {
                Log::channel('predictive')->warning('Using fallback result for expense forecast', [
                    'filters' => $filters,
                    'error' => $e->getMessage()
                ]);
                return $this->deserializeExpenseForecast($fallbackResult);
            }
            
            // Re-throw if no fallback available
            throw $e;
        }
    }

    private function generateForecast(array $filters, int $months): ExpenseForecast
    {
        // Obtener datos históricos
        $historicalData = $this->getHistoricalExpenseData($filters);
        
        if (count($historicalData) < self::MIN_HISTORICAL_MONTHS) {
            throw new InsufficientDataException(
                'gastos', 
                self::MIN_HISTORICAL_MONTHS, 
                count($historicalData)
            );
        }

        // Generar proyecciones para 3, 6 y 12 meses
        $projections = $this->generateProjections($historicalData, $months);
        
        // Desglose por categoría
        $categoryBreakdown = $this->generateCategoryBreakdown($historicalData, $months);
        
        // Calcular correlación con ingresos
        $correlation = $this->calculateExpenseIncomeCorrelation($filters);
        
        // Crear forecast
        $forecast = new ExpenseForecast(
            projections: $projections,
            categoryBreakdown: $categoryBreakdown,
            correlation: $correlation,
            alerts: [],
            metadata: [
                'filters' => $filters,
                'months' => $months,
                'historical_months' => count($historicalData),
                'generated_at' => now()->toISOString()
            ]
        );

        // Verificar alertas de umbral
        $alerts = $this->checkThresholdAlerts($forecast);
        $forecast->alerts = $alerts;

        return $forecast;
    }

    public function calculateCorrelation(array $incomes, array $expenses): float
    {
        if (count($incomes) !== count($expenses) || count($incomes) < 2) {
            return 0.0;
        }

        $n = count($incomes);
        
        // Calcular medias
        $meanIncome = array_sum($incomes) / $n;
        $meanExpense = array_sum($expenses) / $n;
        
        // Calcular numerador y denominadores para Pearson
        $numerator = 0;
        $sumSquaredIncomes = 0;
        $sumSquaredExpenses = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $incomeDeviation = $incomes[$i] - $meanIncome;
            $expenseDeviation = $expenses[$i] - $meanExpense;
            
            $numerator += $incomeDeviation * $expenseDeviation;
            $sumSquaredIncomes += $incomeDeviation * $incomeDeviation;
            $sumSquaredExpenses += $expenseDeviation * $expenseDeviation;
        }
        
        // Evitar división por cero
        $denominator = sqrt($sumSquaredIncomes * $sumSquaredExpenses);
        if ($denominator == 0) {
            return 0.0;
        }
        
        $correlation = $numerator / $denominator;
        
        // Asegurar que esté en el rango [-1, 1]
        return max(-1, min(1, $correlation));
    }

    public function checkThresholdAlerts(ExpenseForecast $forecast): array
    {
        $alerts = [];
        
        // Obtener umbral de configuración
        $threshold = $this->getAlertThreshold();
        
        // Calcular promedio histórico
        $historicalAverage = $this->calculateHistoricalAverage($forecast->metadata['filters'] ?? []);
        
        foreach ($forecast->projections as $period => $projection) {
            $projectedAmount = $projection['total'] ?? 0;
            $thresholdAmount = $historicalAverage * (1 + $threshold / 100);
            
            if ($projectedAmount > $thresholdAmount) {
                $alerts[] = [
                    'type' => 'expense_threshold_exceeded',
                    'period' => $period,
                    'projected_amount' => $projectedAmount,
                    'threshold_amount' => $thresholdAmount,
                    'excess_percentage' => (($projectedAmount - $historicalAverage) / $historicalAverage) * 100,
                    'message' => "Los gastos proyectados para {$period} exceden el umbral configurado en " . 
                                number_format((($projectedAmount - $thresholdAmount) / $thresholdAmount) * 100, 1) . '%',
                    'severity' => $projectedAmount > ($thresholdAmount * 1.5) ? 'high' : 'medium'
                ];
            }
        }
        
        // Alertas por categoría
        foreach ($forecast->categoryBreakdown as $category => $breakdown) {
            $categoryAverage = $this->calculateCategoryHistoricalAverage($category, $forecast->metadata['filters'] ?? []);
            
            foreach ($breakdown['projections'] as $period => $amount) {
                $categoryThreshold = $categoryAverage * (1 + $threshold / 100);
                
                if ($amount > $categoryThreshold) {
                    $alerts[] = [
                        'type' => 'category_threshold_exceeded',
                        'category' => $category,
                        'period' => $period,
                        'projected_amount' => $amount,
                        'threshold_amount' => $categoryThreshold,
                        'message' => "Los gastos de {$category} para {$period} exceden el umbral en " . 
                                    number_format((($amount - $categoryThreshold) / $categoryThreshold) * 100, 1) . '%',
                        'severity' => 'medium'
                    ];
                }
            }
        }
        
        return $alerts;
    }

    private function getHistoricalExpenseData(array $filters): array
    {
        // Usar el scope optimizado para obtener datos con eager loading
        $query = Repase::forPrediction($filters)
            ->orderBy('fecha');

        $repases = $query->get();
        
        // Agrupar por mes
        $monthlyData = [];
        foreach ($repases as $repase) {
            $month = $repase->fecha->format('Y-m');
            
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = [
                    'month' => $month,
                    'total_expenses' => 0,
                    'total_income' => 0,
                    'categories' => array_fill_keys(self::EXPENSE_CATEGORIES, 0)
                ];
            }
            
            $monthlyData[$month]['total_expenses'] += $repase->total_gastos ?? 0;
            $monthlyData[$month]['total_income'] += $repase->total_neto ?? 0;
            
            // Categorizar gastos
            foreach ($repase->gastos as $gasto) {
                $category = $this->categorizeExpense($gasto->tipo);
                $monthlyData[$month]['categories'][$category] += $gasto->monto;
            }
        }
        
        return array_values($monthlyData);
    }

    private function generateProjections(array $historicalData, int $maxMonths): array
    {
        $projections = [];
        $periods = [3, 6, 12];
        
        // Filtrar períodos según el máximo solicitado
        $periods = array_filter($periods, fn($p) => $p <= $maxMonths);
        
        foreach ($periods as $months) {
            $projection = $this->calculateProjection($historicalData, $months);
            $projections["{$months}_months"] = $projection;
        }
        
        return $projections;
    }

    private function calculateProjection(array $historicalData, int $months): array
    {
        // Usar los últimos 12 meses para la proyección
        $recentData = array_slice($historicalData, -12);
        
        // Calcular tendencia lineal
        $trend = $this->calculateLinearTrend($recentData);
        
        // Calcular estacionalidad
        $seasonality = $this->calculateSeasonality($historicalData);
        
        // Proyectar
        $baseAmount = end($recentData)['total_expenses'];
        $projectedAmount = $baseAmount + ($trend * $months);
        
        // Aplicar factor estacional
        $currentMonth = (int) date('m');
        $targetMonth = ($currentMonth + $months - 1) % 12 + 1;
        $seasonalFactor = $seasonality[$targetMonth] ?? 1.0;
        
        $projectedAmount *= $seasonalFactor;
        
        return [
            'total' => max(0, $projectedAmount),
            'trend' => $trend,
            'seasonal_factor' => $seasonalFactor,
            'confidence_interval' => $this->calculateConfidenceInterval($recentData, $projectedAmount)
        ];
    }

    private function generateCategoryBreakdown(array $historicalData, int $maxMonths): array
    {
        $breakdown = [];
        $periods = array_filter([3, 6, 12], fn($p) => $p <= $maxMonths);
        
        foreach (self::EXPENSE_CATEGORIES as $category) {
            $categoryData = array_map(fn($data) => $data['categories'][$category], $historicalData);
            $categoryTrend = $this->calculateLinearTrend($categoryData);
            
            $projections = [];
            foreach ($periods as $months) {
                $baseAmount = end($categoryData);
                $projectedAmount = max(0, $baseAmount + ($categoryTrend * $months));
                $projections["{$months}_months"] = $projectedAmount;
            }
            
            $breakdown[$category] = [
                'projections' => $projections,
                'trend' => $categoryTrend,
                'historical_average' => array_sum($categoryData) / count($categoryData)
            ];
        }
        
        return $breakdown;
    }

    private function calculateExpenseIncomeCorrelation(array $filters): float
    {
        $historicalData = $this->getHistoricalExpenseData($filters);
        
        $incomes = array_map(fn($data) => $data['total_income'], $historicalData);
        $expenses = array_map(fn($data) => $data['total_expenses'], $historicalData);
        
        return $this->calculateCorrelation($incomes, $expenses);
    }

    private function calculateLinearTrend(array $data): float
    {
        $n = count($data);
        if ($n < 2) return 0;
        
        // Extraer valores numéricos
        $values = is_array($data[0]) ? array_map(fn($d) => $d['total_expenses'] ?? 0, $data) : $data;
        
        $sumX = array_sum(range(1, $n));
        $sumY = array_sum($values);
        $sumXY = 0;
        $sumX2 = 0;
        
        for ($i = 0; $i < $n; $i++) {
            $x = $i + 1;
            $y = $values[$i];
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }
        
        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) return 0;
        
        return (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
    }

    private function calculateSeasonality(array $historicalData): array
    {
        $monthlyAverages = array_fill(1, 12, []);
        
        foreach ($historicalData as $data) {
            $month = (int) date('m', strtotime($data['month'] . '-01'));
            $monthlyAverages[$month][] = $data['total_expenses'];
        }
        
        $seasonality = [];
        $overallAverage = array_sum(array_map(fn($d) => $d['total_expenses'], $historicalData)) / count($historicalData);
        
        for ($month = 1; $month <= 12; $month++) {
            if (!empty($monthlyAverages[$month])) {
                $monthAverage = array_sum($monthlyAverages[$month]) / count($monthlyAverages[$month]);
                $seasonality[$month] = $overallAverage > 0 ? $monthAverage / $overallAverage : 1.0;
            } else {
                $seasonality[$month] = 1.0;
            }
        }
        
        return $seasonality;
    }

    private function calculateConfidenceInterval(array $data, float $projection): array
    {
        $values = array_map(fn($d) => $d['total_expenses'], $data);
        $mean = array_sum($values) / count($values);
        
        // Calcular desviación estándar
        $variance = array_sum(array_map(fn($v) => pow($v - $mean, 2), $values)) / count($values);
        $stdDev = sqrt($variance);
        
        // Intervalo de confianza del 95% (aproximadamente 1.96 * stdDev)
        $margin = 1.96 * $stdDev;
        
        return [
            'lower' => max(0, $projection - $margin),
            'upper' => $projection + $margin
        ];
    }

    private function categorizeExpense(string $tipo): string
    {
        // Map actual enum values to expected categories
        return match($tipo) {
            'doctor', 'tecnico' => 'personal',
            'laudos' => 'suministros', 
            'gasolina' => 'otros',
            'extra' => 'otros',
            default => 'otros'
        };
    }

    private function getAlertThreshold(): float
    {
        return $this->config->getWithOverride('expense_alert_threshold', 25.0);
    }

    private function calculateHistoricalAverage(array $filters): float
    {
        $historicalData = $this->getHistoricalExpenseData($filters);
        
        if (empty($historicalData)) {
            return 0.0;
        }
        
        $totalExpenses = array_sum(array_map(fn($data) => $data['total_expenses'], $historicalData));
        return $totalExpenses / count($historicalData);
    }

    private function calculateCategoryHistoricalAverage(string $category, array $filters): float
    {
        $historicalData = $this->getHistoricalExpenseData($filters);
        
        if (empty($historicalData)) {
            return 0.0;
        }
        
        $categoryExpenses = array_sum(array_map(fn($data) => $data['categories'][$category], $historicalData));
        return $categoryExpenses / count($historicalData);
    }

    /**
     * Serialize ExpenseForecast for caching
     */
    private function serializeExpenseForecast(ExpenseForecast $forecast): array
    {
        return [
            'projections' => $forecast->projections,
            'categoryBreakdown' => $forecast->categoryBreakdown,
            'correlation' => $forecast->correlation,
            'alerts' => $forecast->alerts,
            'metadata' => $forecast->metadata
        ];
    }

    /**
     * Deserialize cached data back to ExpenseForecast
     */
    private function deserializeExpenseForecast(array $data): ExpenseForecast
    {
        return new ExpenseForecast(
            projections: $data['projections'],
            categoryBreakdown: $data['categoryBreakdown'],
            correlation: $data['correlation'],
            alerts: $data['alerts'],
            metadata: $data['metadata']
        );
    }
}