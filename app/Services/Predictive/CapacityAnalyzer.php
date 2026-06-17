<?php

namespace App\Services\Predictive;

use App\Contracts\Predictive\CapacityAnalyzerInterface;
use App\DTOs\Predictive\CapacityAnalysis;
use App\Models\Repase;
use App\Models\Clinica;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class CapacityAnalyzer implements CapacityAnalyzerInterface
{
    private const DEFAULT_MAX_CAPACITY_PER_CLINIC = 1000; // Exámenes por mes por clínica
    private const CAPACITY_ALERT_THRESHOLD = 85; // 85% utilización
    private const MINIMUM_MONTHS_FOR_PROJECTION = 6;

    public function analyzeCurrentCapacity(array $filters): CapacityAnalysis
    {
        Log::channel('predictive')->info('Analyzing capacity', [
            'filters' => $filters
        ]);

        try {
            // Calcular utilización actual por clínica
            $clinicUtilization = $this->calculateClinicUtilization($filters);
            
            // Calcular utilización promedio general
            $currentUtilization = $this->calculateOverallUtilization($clinicUtilization);
            
            // Proyectar fecha de saturación
            $projectedSaturationDate = $this->projectSaturationDate($filters);
            
            // Detectar cuellos de botella
            $bottlenecks = $this->detectBottlenecks($clinicUtilization);
            
            // Generar recomendaciones
            $recommendations = $this->generateRecommendations($clinicUtilization, $currentUtilization);

            return new CapacityAnalysis(
                currentUtilization: $currentUtilization,
                clinicUtilization: $clinicUtilization,
                projectedSaturationDate: $projectedSaturationDate,
                bottlenecks: $bottlenecks,
                recommendations: $recommendations,
                metadata: [
                    'filters' => $filters,
                    'analysis_date' => Carbon::now()->toDateString(),
                    'capacity_threshold' => self::CAPACITY_ALERT_THRESHOLD
                ]
            );
        } catch (\Exception $e) {
            Log::channel('predictive')->error('Error analyzing capacity', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            
            throw $e;
        }
    }

    public function projectSaturationDate(array $filters): ?Carbon
    {
        try {
            // Obtener datos históricos para análisis de tendencias
            $historicalData = $this->getHistoricalExamData($filters);
            
            if (count($historicalData) < self::MINIMUM_MONTHS_FOR_PROJECTION) {
                Log::channel('predictive')->warning('Insufficient data for saturation projection', [
                    'available_months' => count($historicalData),
                    'required_months' => self::MINIMUM_MONTHS_FOR_PROJECTION
                ]);
                return null;
            }

            // Calcular tendencia de crecimiento mensual
            $growthTrend = $this->calculateGrowthTrend($historicalData);
            
            if ($growthTrend <= 0) {
                // No hay crecimiento, no se proyecta saturación
                return null;
            }

            // Obtener capacidad máxima total
            $maxCapacity = $this->getMaxCapacity($filters);
            
            // Obtener utilización actual
            $currentExams = $this->getCurrentMonthExams($filters);
            
            // Calcular meses hasta saturación
            $monthsToSaturation = ($maxCapacity - $currentExams) / $growthTrend;
            
            if ($monthsToSaturation <= 0) {
                // Ya estamos en saturación
                return Carbon::now();
            }

            return Carbon::now()->addMonths((int) ceil($monthsToSaturation));
            
        } catch (\Exception $e) {
            Log::channel('predictive')->error('Error projecting saturation date', [
                'error' => $e->getMessage(),
                'filters' => $filters
            ]);
            
            return null;
        }
    }

    public function recommendActions(CapacityAnalysis $analysis): array
    {
        $recommendations = [];

        // Recomendaciones basadas en utilización general
        if ($analysis->currentUtilization >= self::CAPACITY_ALERT_THRESHOLD) {
            $recommendations[] = [
                'type' => 'capacity_alert',
                'priority' => 'high',
                'title' => 'Capacidad crítica alcanzada',
                'description' => sprintf(
                    'La utilización actual del %.1f%% supera el umbral de alerta del %d%%',
                    $analysis->currentUtilization,
                    self::CAPACITY_ALERT_THRESHOLD
                ),
                'actions' => [
                    'Considerar expandir la capacidad operativa',
                    'Optimizar procesos para aumentar eficiencia',
                    'Redistribuir carga entre clínicas'
                ]
            ];
        }

        // Recomendaciones por clínica específica
        foreach ($analysis->clinicUtilization as $clinicData) {
            if ($clinicData['utilization_percentage'] >= self::CAPACITY_ALERT_THRESHOLD) {
                $recommendations[] = [
                    'type' => 'clinic_capacity',
                    'priority' => 'medium',
                    'title' => "Capacidad crítica en {$clinicData['clinic_name']}",
                    'description' => sprintf(
                        'La clínica %s tiene una utilización del %.1f%%',
                        $clinicData['clinic_name'],
                        $clinicData['utilization_percentage']
                    ),
                    'actions' => [
                        'Revisar horarios de atención',
                        'Considerar personal adicional',
                        'Evaluar redistribución de exámenes'
                    ]
                ];
            }
        }

        // Recomendaciones basadas en fecha de saturación
        if ($analysis->projectedSaturationDate) {
            $monthsToSaturation = Carbon::now()->diffInMonths($analysis->projectedSaturationDate);
            
            if ($monthsToSaturation <= 6) {
                $recommendations[] = [
                    'type' => 'saturation_warning',
                    'priority' => 'high',
                    'title' => 'Saturación proyectada en corto plazo',
                    'description' => sprintf(
                        'Se proyecta saturación de capacidad para %s (%d meses)',
                        $analysis->projectedSaturationDate->format('M Y'),
                        $monthsToSaturation
                    ),
                    'actions' => [
                        'Planificar expansión de capacidad inmediatamente',
                        'Implementar medidas de eficiencia operativa',
                        'Considerar alianzas estratégicas'
                    ]
                ];
            }
        }

        // Recomendaciones para cuellos de botella
        foreach ($analysis->bottlenecks as $bottleneck) {
            $recommendations[] = [
                'type' => 'bottleneck',
                'priority' => 'medium',
                'title' => "Cuello de botella detectado: {$bottleneck['type']}",
                'description' => $bottleneck['description'],
                'actions' => $bottleneck['suggested_actions']
            ];
        }

        return $recommendations;
    }

    private function calculateClinicUtilization(array $filters): array
    {
        $clinics = Clinica::all();
        $utilization = [];

        foreach ($clinics as $clinic) {
            $clinicFilters = array_merge($filters, ['clinica_id' => $clinic->id]);
            
            // Obtener número de exámenes del mes actual
            $currentExams = $this->getCurrentMonthExams($clinicFilters);
            
            // Capacidad máxima por clínica (configurable)
            $maxCapacity = $this->getMaxCapacityForClinic($clinic->id);
            
            // Calcular porcentaje de utilización
            $utilizationPercentage = $maxCapacity > 0 ? ($currentExams / $maxCapacity) * 100 : 0;
            
            // Calcular tendencia de crecimiento
            $growthTrend = $this->calculateClinicGrowthTrend($clinic->id, $filters);

            $utilization[] = [
                'clinic_id' => $clinic->id,
                'clinic_name' => $clinic->nombre,
                'current_exams' => $currentExams,
                'max_capacity' => $maxCapacity,
                'utilization_percentage' => round($utilizationPercentage, 2),
                'growth_trend' => round($growthTrend, 2),
                'status' => $this->getUtilizationStatus($utilizationPercentage)
            ];
        }

        return $utilization;
    }

    private function calculateOverallUtilization(array $clinicUtilization): float
    {
        if (empty($clinicUtilization)) {
            return 0.0;
        }

        $totalExams = array_sum(array_column($clinicUtilization, 'current_exams'));
        $totalCapacity = array_sum(array_column($clinicUtilization, 'max_capacity'));

        return $totalCapacity > 0 ? ($totalExams / $totalCapacity) * 100 : 0.0;
    }

    private function detectBottlenecks(array $clinicUtilization): array
    {
        $bottlenecks = [];

        foreach ($clinicUtilization as $clinic) {
            if ($clinic['utilization_percentage'] >= 90) {
                $bottlenecks[] = [
                    'type' => 'capacity_bottleneck',
                    'clinic_id' => $clinic['clinic_id'],
                    'clinic_name' => $clinic['clinic_name'],
                    'description' => sprintf(
                        'La clínica %s está operando al %.1f%% de su capacidad',
                        $clinic['clinic_name'],
                        $clinic['utilization_percentage']
                    ),
                    'severity' => 'high',
                    'suggested_actions' => [
                        'Redistribuir exámenes a otras clínicas',
                        'Extender horarios de atención',
                        'Aumentar personal técnico'
                    ]
                ];
            }

            if ($clinic['growth_trend'] > 20) {
                $bottlenecks[] = [
                    'type' => 'growth_bottleneck',
                    'clinic_id' => $clinic['clinic_id'],
                    'clinic_name' => $clinic['clinic_name'],
                    'description' => sprintf(
                        'La clínica %s muestra un crecimiento acelerado del %.1f%% mensual',
                        $clinic['clinic_name'],
                        $clinic['growth_trend']
                    ),
                    'severity' => 'medium',
                    'suggested_actions' => [
                        'Planificar expansión de capacidad',
                        'Monitorear tendencias de demanda',
                        'Evaluar recursos adicionales'
                    ]
                ];
            }
        }

        return $bottlenecks;
    }

    private function generateRecommendations(array $clinicUtilization, float $overallUtilization): array
    {
        $recommendations = [];

        // Recomendaciones generales
        if ($overallUtilization < 50) {
            $recommendations[] = 'Considerar estrategias de marketing para aumentar la demanda';
            $recommendations[] = 'Evaluar optimización de costos operativos';
        } elseif ($overallUtilization > 85) {
            $recommendations[] = 'Planificar expansión de capacidad a corto plazo';
            $recommendations[] = 'Implementar sistemas de gestión de citas más eficientes';
        }

        // Recomendaciones específicas por clínica
        $highUtilizationClinics = array_filter($clinicUtilization, 
            fn($clinic) => $clinic['utilization_percentage'] > 85
        );

        if (!empty($highUtilizationClinics)) {
            $clinicNames = array_column($highUtilizationClinics, 'clinic_name');
            $recommendations[] = sprintf(
                'Revisar capacidad en clínicas: %s',
                implode(', ', $clinicNames)
            );
        }

        return $recommendations;
    }

    private function getHistoricalExamData(array $filters): array
    {
        $startDate = Carbon::now()->subMonths(24)->format('Y-m-d');
        
        // Usar el scope optimizado para análisis de capacidad
        $filters['fecha_inicio'] = $startDate;
        
        $results = Repase::forCapacityAnalysis($filters)->get();
        
        // Agrupar por mes
        $monthlyData = [];
        foreach ($results as $result) {
            $month = $result->fecha->format('Y-m');
            $key = $month . '_' . $result->clinica_id;
            
            if (!isset($monthlyData[$key])) {
                $monthlyData[$key] = [
                    'month' => $month,
                    'total_exams' => 0,
                    'clinica_id' => $result->clinica_id
                ];
            }
            
            $monthlyData[$key]['total_exams'] += $result->total_examenes_count;
        }

        return array_values($monthlyData);
    }

    private function calculateGrowthTrend(array $historicalData): float
    {
        if (count($historicalData) < 2) {
            return 0.0;
        }

        // Agrupar por mes
        $monthlyTotals = [];
        foreach ($historicalData as $data) {
            $month = $data['month'];
            if (!isset($monthlyTotals[$month])) {
                $monthlyTotals[$month] = 0;
            }
            $monthlyTotals[$month] += $data['total_exams'];
        }

        $months = array_keys($monthlyTotals);
        $values = array_values($monthlyTotals);

        // Calcular regresión lineal simple para obtener tendencia
        $n = count($values);
        $sumX = array_sum(range(0, $n - 1));
        $sumY = array_sum($values);
        $sumXY = 0;
        $sumX2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sumXY += $i * $values[$i];
            $sumX2 += $i * $i;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);

        return $slope; // Crecimiento promedio mensual
    }

    private function getMaxCapacity(array $filters): int
    {
        if (isset($filters['clinica_id'])) {
            return $this->getMaxCapacityForClinic($filters['clinica_id']);
        }

        // Capacidad total de todas las clínicas
        $clinics = Clinica::all();
        return $clinics->count() * self::DEFAULT_MAX_CAPACITY_PER_CLINIC;
    }

    private function getMaxCapacityForClinic(int $clinicId): int
    {
        // Por ahora usamos capacidad por defecto
        // En el futuro esto podría venir de una tabla de configuración
        return self::DEFAULT_MAX_CAPACITY_PER_CLINIC;
    }

    private function getCurrentMonthExams(array $filters): int
    {
        // Usar el último mes disponible en lugar del mes actual
        $latestRepase = Repase::orderBy('fecha', 'desc')->first();
        
        if (!$latestRepase) {
            return 0;
        }
        
        $latestMonth = Carbon::parse($latestRepase->fecha);
        $startOfMonth = $latestMonth->startOfMonth()->format('Y-m-d');
        $endOfMonth = $latestMonth->endOfMonth()->format('Y-m-d');
        
        // Usar el scope optimizado para análisis de capacidad
        $filters['fecha_inicio'] = $startOfMonth;
        $filters['fecha_fin'] = $endOfMonth;
        
        $results = Repase::forCapacityAnalysis($filters)->get();
        
        return $results->sum('total_examenes_count');
    }

    private function calculateClinicGrowthTrend(int $clinicId, array $filters): float
    {
        $clinicFilters = array_merge($filters, ['clinica_id' => $clinicId]);
        $historicalData = $this->getHistoricalExamData($clinicFilters);
        
        return $this->calculateGrowthTrend($historicalData);
    }

    private function getUtilizationStatus(float $utilizationPercentage): string
    {
        if ($utilizationPercentage >= 90) {
            return 'critical';
        } elseif ($utilizationPercentage >= 75) {
            return 'high';
        } elseif ($utilizationPercentage >= 50) {
            return 'normal';
        } else {
            return 'low';
        }
    }
}