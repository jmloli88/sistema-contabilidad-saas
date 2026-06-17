<?php

namespace App\Services\Predictive;

use App\Contracts\Predictive\IncomePredictorInterface;
use App\Contracts\Predictive\CacheServiceInterface;
use App\DTOs\Predictive\PredictionResult;
use App\Models\Repase;
use App\Exceptions\Predictive\InsufficientDataException;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class IncomePredictor implements IncomePredictorInterface
{
    private const MIN_HISTORICAL_MONTHS = 12;
    private const AVAILABLE_ALGORITHMS = ['linear_regression', 'moving_average', 'seasonal'];

    public function __construct(
        private CacheServiceInterface $cacheService
    ) {}

    public function predictIncome(array $filters, int $months): PredictionResult
    {
        Log::channel('predictive')->info('Generating income prediction', [
            'filters' => $filters,
            'months' => $months
        ]);

        // Try to get cached result first
        $cachedResult = $this->cacheService->getCachedPrediction('income', $filters);
        if ($cachedResult !== null) {
            Log::channel('predictive')->info('Returning cached income prediction', [
                'filters' => $filters
            ]);
            return $this->deserializePredictionResult($cachedResult);
        }

        try {
            // Generate new prediction
            $result = $this->generatePrediction($filters, $months);
            
            // Cache the result
            $this->cacheService->cachePrediction('income', $filters, $this->serializePredictionResult($result));
            
            return $result;
            
        } catch (\Exception $e) {
            // Try fallback from cache service
            $fallbackResult = $this->cacheService->getFallbackResult('income', $filters, $e);
            
            if ($fallbackResult !== null) {
                Log::channel('predictive')->warning('Using fallback result for income prediction', [
                    'filters' => $filters,
                    'error' => $e->getMessage()
                ]);
                return $this->deserializePredictionResult($fallbackResult);
            }
            
            // Re-throw if no fallback available
            throw $e;
        }
    }

    private function generatePrediction(array $filters, int $months): PredictionResult
    {
        // Obtener datos históricos
        $historicalData = $this->getHistoricalData($filters);
        
        // Validar suficiencia de datos
        if (count($historicalData) < self::MIN_HISTORICAL_MONTHS) {
            throw new InsufficientDataException(
                'predicción de ingresos',
                self::MIN_HISTORICAL_MONTHS,
                count($historicalData)
            );
        }

        // Determinar algoritmo a usar (por defecto linear_regression)
        $algorithm = $filters['algorithm'] ?? 'linear_regression';
        
        // Generar proyecciones para 3, 6 y 12 meses
        $projections = [
            '3_months' => $this->calculateProjection($historicalData, $algorithm, 3),
            '6_months' => $this->calculateProjection($historicalData, $algorithm, 6),
            '12_months' => $this->calculateProjection($historicalData, $algorithm, 12),
        ];

        // Calcular precisión si hay datos suficientes
        $accuracy = $this->calculateAccuracy($algorithm, $historicalData);

        return new PredictionResult(
            projections: $projections,
            algorithm: $algorithm,
            metadata: [
                'filters' => $filters,
                'months' => $months,
                'historical_data_points' => count($historicalData),
                'generated_at' => Carbon::now()->toISOString()
            ],
            accuracy: $accuracy
        );
    }

    public function getAvailableAlgorithms(): array
    {
        return self::AVAILABLE_ALGORITHMS;
    }

    public function calculateAccuracy(string $algorithm, array $historicalData): float
    {
        if (count($historicalData) < 24) {
            return 0.0; // Necesitamos al menos 24 meses para calcular precisión
        }

        // Usar los últimos 12 meses como datos de prueba
        $testData = array_slice($historicalData, -12);
        $trainingData = array_slice($historicalData, 0, -12);

        $totalError = 0;
        $totalActual = 0;

        foreach ($testData as $index => $actual) {
            $predicted = $this->calculateProjection($trainingData, $algorithm, $index + 1);
            $error = abs($actual['total_ingresos'] - $predicted);
            $totalError += $error;
            $totalActual += $actual['total_ingresos'];
        }

        // Calcular MAPE (Mean Absolute Percentage Error)
        $mape = ($totalError / $totalActual) * 100;
        
        // Convertir a precisión (100 - MAPE)
        return max(0, 100 - $mape);
    }

    private function getHistoricalData(array $filters): array
    {
        $query = Repase::forPrediction($filters)
            ->groupedByMonth();

        // Si no hay filtro de fecha, obtener últimos 36 meses
        if (!isset($filters['fecha_inicio'])) {
            $query->where('fecha', '>=', Carbon::now()->subMonths(36));
        }

        $results = $query->get()->toArray();

        // Convertir a formato estándar
        return array_map(function ($item) {
            return [
                'month' => $item['month'],
                'total_ingresos' => (float) $item['total_ingresos'],
                'total_repases' => (int) $item['total_repases'],
                'clinica_id' => $item['clinica_id']
            ];
        }, $results);
    }

    private function calculateProjection(array $historicalData, string $algorithm, int $monthsAhead): float
    {
        switch ($algorithm) {
            case 'linear_regression':
                return $this->linearRegressionProjection($historicalData, $monthsAhead);
            case 'moving_average':
                return $this->movingAverageProjection($historicalData, $monthsAhead);
            case 'seasonal':
                return $this->seasonalProjection($historicalData, $monthsAhead);
            default:
                return $this->linearRegressionProjection($historicalData, $monthsAhead);
        }
    }

    private function linearRegressionProjection(array $data, int $monthsAhead): float
    {
        $n = count($data);
        if ($n < 2) return 0.0;

        $sumX = 0;
        $sumY = 0;
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($data as $index => $point) {
            $x = $index + 1; // Mes como variable independiente
            $y = $point['total_ingresos'];
            
            $sumX += $x;
            $sumY += $y;
            $sumXY += $x * $y;
            $sumX2 += $x * $x;
        }

        // Calcular pendiente (m) e intercepto (b) de y = mx + b
        $denominator = ($n * $sumX2) - ($sumX * $sumX);
        if ($denominator == 0) return $sumY / $n; // Fallback al promedio

        $slope = (($n * $sumXY) - ($sumX * $sumY)) / $denominator;
        $intercept = ($sumY - ($slope * $sumX)) / $n;

        // Proyectar para el mes futuro
        $futureX = $n + $monthsAhead;
        return max(0, $slope * $futureX + $intercept);
    }

    private function movingAverageProjection(array $data, int $monthsAhead): float
    {
        $n = count($data);
        if ($n == 0) return 0.0;

        // Usar promedio móvil de los últimos 6 meses (o todos si hay menos)
        $windowSize = min(6, $n);
        $recentData = array_slice($data, -$windowSize);
        
        $sum = array_sum(array_column($recentData, 'total_ingresos'));
        $average = $sum / $windowSize;

        // Calcular tendencia simple para ajustar la proyección
        if ($n >= 3) {
            $recent3 = array_slice($data, -3);
            $older3 = array_slice($data, -6, 3);
            
            if (count($older3) > 0) {
                $recentAvg = array_sum(array_column($recent3, 'total_ingresos')) / count($recent3);
                $olderAvg = array_sum(array_column($older3, 'total_ingresos')) / count($older3);
                $trendFactor = $recentAvg / max($olderAvg, 1);
                
                // Aplicar tendencia gradualmente según meses adelante
                $trendAdjustment = pow($trendFactor, $monthsAhead / 12);
                return max(0, $average * $trendAdjustment);
            }
        }

        return max(0, $average);
    }

    private function seasonalProjection(array $data, int $monthsAhead): float
    {
        $n = count($data);
        if ($n < 12) {
            // Si no hay suficientes datos para análisis estacional, usar regresión lineal
            return $this->linearRegressionProjection($data, $monthsAhead);
        }

        // Calcular patrones estacionales por mes
        $monthlyData = [];
        foreach ($data as $point) {
            $month = (int) date('n', strtotime($point['month'] . '-01'));
            if (!isset($monthlyData[$month])) {
                $monthlyData[$month] = [];
            }
            $monthlyData[$month][] = $point['total_ingresos'];
        }

        // Calcular promedio por mes
        $monthlyAverages = [];
        $overallAverage = array_sum(array_column($data, 'total_ingresos')) / $n;
        
        for ($month = 1; $month <= 12; $month++) {
            if (isset($monthlyData[$month])) {
                $monthlyAverages[$month] = array_sum($monthlyData[$month]) / count($monthlyData[$month]);
            } else {
                $monthlyAverages[$month] = $overallAverage;
            }
        }

        // Calcular tendencia base usando regresión lineal
        $baseTrend = $this->linearRegressionProjection($data, $monthsAhead);
        
        // Determinar el mes objetivo
        $currentMonth = (int) date('n');
        $targetMonth = (($currentMonth + $monthsAhead - 1) % 12) + 1;
        
        // Aplicar factor estacional
        $seasonalFactor = $monthlyAverages[$targetMonth] / $overallAverage;
        
        return max(0, $baseTrend * $seasonalFactor);
    }

    /**
     * Serialize PredictionResult for caching
     */
    private function serializePredictionResult(PredictionResult $result): array
    {
        return [
            'projections' => $result->projections,
            'algorithm' => $result->algorithm,
            'metadata' => $result->metadata,
            'accuracy' => $result->accuracy
        ];
    }

    /**
     * Deserialize cached data back to PredictionResult
     */
    private function deserializePredictionResult(array $data): PredictionResult
    {
        return new PredictionResult(
            projections: $data['projections'],
            algorithm: $data['algorithm'],
            metadata: $data['metadata'],
            accuracy: $data['accuracy']
        );
    }
}