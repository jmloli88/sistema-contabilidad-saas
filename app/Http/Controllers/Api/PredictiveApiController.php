<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Predictive\IncomePredictor;
use App\Services\Predictive\TrendDetector;
use App\Services\Predictive\ExpenseForecaster;
use App\Services\Predictive\CapacityAnalyzer;
use App\Services\Predictive\PredictiveConfig;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

/**
 * API Controller para endpoints en tiempo real del módulo predictivo
 * 
 * Proporciona endpoints JSON para actualizaciones dinámicas de gráficos
 * y datos predictivos sin recargar la página completa.
 */
class PredictiveApiController extends Controller
{
    public function __construct(
        private IncomePredictor $incomePredictor,
        private TrendDetector $trendDetector,
        private ExpenseForecaster $expenseForecaster,
        private CapacityAnalyzer $capacityAnalyzer,
        private PredictiveConfig $config
    ) {}

    /**
     * Obtiene proyección de ingresos en formato JSON
     * 
     * @param Request $request
     * @param int $months
     * @return JsonResponse
     */
    public function getIncomeProjection(Request $request, int $months): JsonResponse
    {
        try {
            $request->validate([
                'clinica_id' => 'nullable|exists:clinicas,id',
                'fecha_desde' => 'nullable|date',
                'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
                'algorithm' => 'nullable|in:linear_regression,moving_average,seasonal,all'
            ]);

            $filters = [
                'clinica_id' => $request->input('clinica_id'),
                'fecha_desde' => $request->input('fecha_desde'),
                'fecha_hasta' => $request->input('fecha_hasta'),
                'algorithm' => $request->input('algorithm', 'all')
            ];

            $projection = $this->incomePredictor->predictIncome($filters, $months);

            return response()->json([
                'success' => true,
                'data' => [
                    'projections' => [
                        '3_months' => $projection->getProjection('3_months'),
                        '6_months' => $projection->getProjection('6_months'),
                        '12_months' => $projection->getProjection('12_months')
                    ],
                    'algorithms' => $projection->metadata['algorithms'] ?? [$projection->algorithm],
                    'confidence' => $projection->accuracy ?? 0,
                    'trend' => $projection->metadata['trend'] ?? 'stable',
                    'chart_data' => $this->formatIncomeChartData($projection)
                ],
                'meta' => [
                    'generated_at' => now()->toISOString(),
                    'filters_applied' => $filters,
                    'months_requested' => $months
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Datos de entrada inválidos',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            logger()->error('Error en API de predicción de ingresos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene forecast de gastos en formato JSON
     * 
     * @param Request $request
     * @param int $months
     * @return JsonResponse
     */
    public function getExpenseForecast(Request $request, int $months): JsonResponse
    {
        try {
            $request->validate([
                'clinica_id' => 'nullable|exists:clinicas,id',
                'fecha_desde' => 'nullable|date',
                'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde'
            ]);

            $filters = [
                'clinica_id' => $request->input('clinica_id'),
                'fecha_desde' => $request->input('fecha_desde'),
                'fecha_hasta' => $request->input('fecha_hasta')
            ];

            $forecast = $this->expenseForecaster->forecastExpenses($filters, $months);
            $alerts = $this->expenseForecaster->checkThresholdAlerts($forecast);

            return response()->json([
                'success' => true,
                'data' => [
                    'forecast' => [
                        '3_months' => $forecast->projections['3_months']['total'] ?? 0,
                        '6_months' => $forecast->projections['6_months']['total'] ?? 0,
                        '12_months' => $forecast->projections['12_months']['total'] ?? 0
                    ],
                    'by_category' => $forecast->categoryBreakdown,
                    'alerts' => $alerts,
                    'correlation_with_income' => $forecast->correlation,
                    'chart_data' => $this->formatExpenseChartData($forecast)
                ],
                'meta' => [
                    'generated_at' => now()->toISOString(),
                    'filters_applied' => $filters,
                    'months_requested' => $months,
                    'alert_threshold' => $this->config->get('expense_alert_threshold', 25)
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Datos de entrada inválidos',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            logger()->error('Error en API de forecast de gastos: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene análisis de capacidad actual en formato JSON
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getCurrentCapacity(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'clinica_id' => 'nullable|exists:clinicas,id',
                'fecha_desde' => 'nullable|date',
                'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde'
            ]);

            $filters = [
                'clinica_id' => $request->input('clinica_id'),
                'fecha_desde' => $request->input('fecha_desde'),
                'fecha_hasta' => $request->input('fecha_hasta')
            ];

            $analysis = $this->capacityAnalyzer->analyzeCurrentCapacity($filters);
            $saturationDate = $this->capacityAnalyzer->projectSaturationDate($filters);
            $recommendations = $this->capacityAnalyzer->recommendActions($analysis);

            return response()->json([
                'success' => true,
                'data' => [
                    'current_utilization' => $analysis->currentUtilization,
                    'utilization_by_clinic' => $analysis->clinicUtilization,
                    'saturation_date' => $saturationDate?->toISOString(),
                    'days_to_saturation' => $saturationDate ? now()->diffInDays($saturationDate) : null,
                    'bottlenecks' => $analysis->bottlenecks,
                    'recommendations' => $recommendations,
                    'growth_rate' => $analysis->metadata['growth_rate'] ?? 0,
                    'chart_data' => $this->formatCapacityChartData($analysis)
                ],
                'meta' => [
                    'generated_at' => now()->toISOString(),
                    'filters_applied' => $filters,
                    'alert_threshold' => $this->config->get('capacity_alert_threshold', 85)
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Datos de entrada inválidos',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            logger()->error('Error en API de análisis de capacidad: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtiene tendencias estacionales en formato JSON
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getSeasonalTrends(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'clinica_id' => 'nullable|exists:clinicas,id',
                'fecha_desde' => 'nullable|date',
                'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde',
                'min_months' => 'nullable|integer|min:12|max:60'
            ]);

            $filters = [
                'clinica_id' => $request->input('clinica_id'),
                'fecha_desde' => $request->input('fecha_desde'),
                'fecha_hasta' => $request->input('fecha_hasta')
            ];

            $minMonths = (int) $request->input('min_months', 24);
            $historicalData = $this->getHistoricalData($filters);

            if (count($historicalData) < $minMonths) {
                return response()->json([
                    'success' => false,
                    'error' => "Datos insuficientes para análisis estacional. Requeridos: {$minMonths} meses, Disponibles: " . count($historicalData) . " meses"
                ], 400);
            }

            $patterns = $this->trendDetector->detectSeasonalPatterns($historicalData, $minMonths);
            $trendStrength = $this->trendDetector->calculateTrendStrength($historicalData);

            return response()->json([
                'success' => true,
                'data' => [
                    'seasonal_patterns' => $patterns->monthlyPatterns,
                    'trend_strength' => $trendStrength,
                    'peak_months' => $patterns->metadata['peak_months'] ?? [],
                    'low_months' => $patterns->metadata['low_months'] ?? [],
                    'seasonal_strength' => $patterns->seasonalStrength,
                    'trend_direction' => $patterns->metadata['trend_direction'] ?? 'stable',
                    'confidence_intervals' => $patterns->confidenceIntervals,
                    'chart_data' => $this->formatTrendChartData($patterns)
                ],
                'meta' => [
                    'generated_at' => now()->toISOString(),
                    'filters_applied' => $filters,
                    'months_analyzed' => count($historicalData),
                    'confidence_level' => 95
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Datos de entrada inválidos',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            logger()->error('Error en API de tendencias estacionales: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualiza configuración del sistema predictivo
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateConfiguration(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'expense_alert_threshold' => 'nullable|numeric|min:1|max:50',
                'capacity_alert_threshold' => 'nullable|numeric|min:50|max:95',
                'active_algorithms' => 'nullable|array',
                'active_algorithms.*' => 'in:linear_regression,moving_average,seasonal',
                'cache_duration_minutes' => 'nullable|integer|min:5|max:1440',
                'min_historical_months' => 'nullable|integer|min:6|max:60'
            ]);

            $updated = [];
            $userId = auth()->id();

            // Actualizar configuraciones una por una
            foreach ($request->only([
                'expense_alert_threshold',
                'capacity_alert_threshold', 
                'active_algorithms',
                'cache_duration_minutes',
                'min_historical_months'
            ]) as $key => $value) {
                if ($value !== null) {
                    if ($key === 'active_algorithms') {
                        $value = json_encode($value);
                    }
                    
                    $this->config->set($key, $value, $userId);
                    $updated[$key] = $value;
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Configuración actualizada correctamente',
                'data' => [
                    'updated_settings' => $updated,
                    'current_config' => $this->config->getAll()
                ],
                'meta' => [
                    'updated_at' => now()->toISOString(),
                    'updated_by' => auth()->user()->name
                ]
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Datos de configuración inválidos',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            logger()->error('Error al actualizar configuración predictiva: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => 'Error interno al actualizar configuración'
            ], 500);
        }
    }

    /**
     * Formatea datos de ingresos para Chart.js
     */
    private function formatIncomeChartData($projection): array
    {
        return [
            'labels' => ['3 meses', '6 meses', '12 meses'],
            'datasets' => [
                [
                    'label' => 'Predicción de Ingresos',
                    'data' => [
                        $projection->getProjection('3_months'),
                        $projection->getProjection('6_months'),
                        $projection->getProjection('12_months')
                    ],
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'borderColor' => 'rgb(59, 130, 246)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4
                ]
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'legend' => [
                        'position' => 'top'
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Proyección de Ingresos'
                    ]
                ]
            ]
        ];
    }

    /**
     * Formatea datos de gastos para Chart.js
     */
    private function formatExpenseChartData($forecast): array
    {
        return [
            'labels' => ['3 meses', '6 meses', '12 meses'],
            'datasets' => [
                [
                    'label' => 'Predicción de Gastos',
                    'data' => [
                        $forecast->projections['3_months']['total'] ?? 0,
                        $forecast->projections['6_months']['total'] ?? 0,
                        $forecast->projections['12_months']['total'] ?? 0
                    ],
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4
                ]
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'legend' => [
                        'position' => 'top'
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Forecast de Gastos'
                    ]
                ]
            ]
        ];
    }

    /**
     * Formatea datos de capacidad para Chart.js
     */
    private function formatCapacityChartData($analysis): array
    {
        return [
            'labels' => ['Utilización Actual', 'Capacidad Disponible'],
            'datasets' => [
                [
                    'data' => [
                        $analysis->currentUtilization,
                        100 - $analysis->currentUtilization
                    ],
                    'backgroundColor' => [
                        'rgb(34, 197, 94)',
                        'rgb(229, 231, 235)'
                    ],
                    'borderWidth' => 0
                ]
            ],
            'options' => [
                'responsive' => true,
                'plugins' => [
                    'legend' => [
                        'position' => 'bottom'
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Utilización de Capacidad'
                    ]
                ]
            ]
        ];
    }

    /**
     * Formatea datos de tendencias para Chart.js
     */
    private function formatTrendChartData($patterns): array
    {
        return [
            'labels' => ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'],
            'datasets' => [
                [
                    'label' => 'Patrón Estacional (%)',
                    'data' => $patterns->monthlyPatterns,
                    'backgroundColor' => 'rgba(168, 85, 247, 0.1)',
                    'borderColor' => 'rgb(168, 85, 247)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4
                ]
            ],
            'options' => [
                'responsive' => true,
                'scales' => [
                    'y' => [
                        'beginAtZero' => false,
                        'title' => [
                            'display' => true,
                            'text' => 'Variación (%)'
                        ]
                    ]
                ],
                'plugins' => [
                    'legend' => [
                        'position' => 'top'
                    ],
                    'title' => [
                        'display' => true,
                        'text' => 'Patrones Estacionales'
                    ]
                ]
            ]
        ];
    }

    /**
     * Obtiene datos históricos para análisis
     */
    private function getHistoricalData(array $filters): array
    {
        $query = \App\Models\Repase::forPrediction($filters)
            ->groupedByMonth();

        // Si no hay filtro de fecha, obtener últimos 36 meses por defecto
        if (!isset($filters['fecha_desde'])) {
            $query->where('fecha', '>=', now()->subMonths(36));
        }

        $results = $query->get()->toArray();

        // Convertir a formato estándar para análisis de tendencias
        return array_map(function ($item) {
            return [
                'month' => $item['month'],
                'total_ingresos' => (float) $item['total_ingresos'],
                'total_repases' => (int) $item['total_repases'],
                'clinica_id' => $item['clinica_id']
            ];
        }, $results);
    }
}