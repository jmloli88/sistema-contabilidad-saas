<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Clinica extends Model
{
    use HasFactory;

    /**
     * Obtener los usuarios asociados a esta clínica.
     *
     * @return HasMany
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Determina si la clínica tiene al menos una suscripción activa (compartida entre sus miembros).
     *
     * @return bool
     */
    public function hasActiveSubscription(): bool
    {
        return $this->users()->whereHas('subscriptions', function ($q) {
            $q->where('stripe_status', 'active')->where('ends_at', '>', now());
        })->exists();
    }

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
    ];

    /**
     * Obtener los repases asociados a esta clínica.
     *
     * @return HasMany
     */
    public function repases(): HasMany
    {
        return $this->hasMany(Repase::class);
    }

    /**
     * Obtener las agendas asociadas a esta clínica.
     *
     * @return HasMany
     */
    public function agendas(): HasMany
    {
        return $this->hasMany(\App\Models\Agenda::class);
    }

    /**
     * Exámenes con precios personalizados para esta clínica.
     *
     * @return BelongsToMany
     */
    public function examenes(): BelongsToMany
    {
        return $this->belongsToMany(Examen::class, 'clinica_examen')
            ->withPivot(['precio_sin_nota', 'precio_con_nota'])
            ->withTimestamps();
    }

    // ========================================
    // CAPACITY ANALYSIS EXTENSIONS
    // ========================================

    /**
     * Scope: Optimizado para análisis de capacidad operativa
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters Filtros disponibles:
     *   - fecha_inicio: Fecha de inicio (Y-m-d)
     *   - fecha_fin: Fecha de fin (Y-m-d)
     *   - include_stats: Incluir estadísticas detalladas (default: true)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCapacityAnalysis($query, array $filters = [])
    {
        // Eager loading optimizado para análisis de capacidad
        $query = $query->with(['repases' => function ($q) use ($filters) {
            $q->select('id', 'clinica_id', 'fecha', 'total_neto')
              ->when($filters['fecha_inicio'] ?? null, fn($subQ, $fecha) => $subQ->where('fecha', '>=', $fecha))
              ->when($filters['fecha_fin'] ?? null, fn($subQ, $fecha) => $subQ->where('fecha', '<=', $fecha))
              ->orderBy('fecha');
        }]);

        // Incluir estadísticas detalladas si se solicita
        if (!isset($filters['include_stats']) || $filters['include_stats'] !== false) {
            $query = $this->addCapacityStatistics($query, $filters);
        }

        return $query->orderBy('nombre');
    }

    /**
     * Scope: Análisis de utilización mensual por clínica
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMonthlyUtilization($query, array $filters = [])
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        $dateFormat = $connection === 'sqlite' 
            ? "strftime('%Y-%m', repases.fecha)" 
            : "DATE_FORMAT(repases.fecha, '%Y-%m')";

        return $query->select([
                'clinicas.id',
                'clinicas.nombre',
                \DB::raw("{$dateFormat} as month"),
                \DB::raw('COUNT(repases.id) as total_repases'),
                \DB::raw('COUNT(DISTINCT repase_examenes.id) as total_examenes'),
                \DB::raw('SUM(repases.total_neto) as total_ingresos'),
                \DB::raw('AVG(repases.total_neto) as promedio_ingresos')
            ])
            ->leftJoin('repases', 'clinicas.id', '=', 'repases.clinica_id')
            ->leftJoin('repase_examenes', 'repases.id', '=', 'repase_examenes.repase_id')
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '<=', $fecha))
            ->groupBy('clinicas.id', 'clinicas.nombre', 'month')
            ->orderBy('clinicas.nombre')
            ->orderBy('month');
    }

    /**
     * Scope: Análisis de crecimiento y tendencias por clínica
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGrowthAnalysis($query, array $filters = [])
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        // Subconsulta para calcular métricas por clínica y mes
        $monthlyMetrics = \DB::table('clinicas')
            ->select([
                'clinicas.id as clinica_id',
                'clinicas.nombre',
                \DB::raw($connection === 'sqlite' 
                    ? "strftime('%Y-%m', repases.fecha) as month"
                    : "DATE_FORMAT(repases.fecha, '%Y-%m') as month"),
                \DB::raw('COUNT(repases.id) as monthly_repases'),
                \DB::raw('COUNT(DISTINCT repase_examenes.id) as monthly_examenes'),
                \DB::raw('SUM(repases.total_neto) as monthly_ingresos')
            ])
            ->leftJoin('repases', 'clinicas.id', '=', 'repases.clinica_id')
            ->leftJoin('repase_examenes', 'repases.id', '=', 'repase_examenes.repase_id')
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '<=', $fecha))
            ->groupBy('clinicas.id', 'clinicas.nombre', 'month');

        return $query->joinSub($monthlyMetrics, 'monthly_data', function ($join) {
                $join->on('clinicas.id', '=', 'monthly_data.clinica_id');
            })
            ->select([
                'clinicas.id',
                'clinicas.nombre',
                \DB::raw('AVG(monthly_data.monthly_repases) as avg_monthly_repases'),
                \DB::raw('AVG(monthly_data.monthly_examenes) as avg_monthly_examenes'),
                \DB::raw('AVG(monthly_data.monthly_ingresos) as avg_monthly_ingresos'),
                \DB::raw('MIN(monthly_data.monthly_repases) as min_monthly_repases'),
                \DB::raw('MAX(monthly_data.monthly_repases) as max_monthly_repases'),
                \DB::raw('COUNT(DISTINCT monthly_data.month) as months_with_data')
            ])
            ->groupBy('clinicas.id', 'clinicas.nombre')
            ->orderBy('avg_monthly_ingresos', 'desc');
    }

    /**
     * Scope: Comparación de rendimiento entre clínicas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePerformanceComparison($query, array $filters = [])
    {
        return $query->select([
                'clinicas.id',
                'clinicas.nombre',
                'clinicas.direccion',
                'clinicas.telefono',
                \DB::raw('COUNT(DISTINCT repases.id) as total_repases'),
                \DB::raw('COUNT(DISTINCT repase_examenes.id) as total_examenes'),
                \DB::raw('SUM(repases.total_neto) as total_ingresos'),
                \DB::raw('AVG(repases.total_neto) as promedio_por_repase'),
                \DB::raw('SUM(gastos.monto) as total_gastos'),
                \DB::raw('(SUM(repases.total_neto) - COALESCE(SUM(gastos.monto), 0)) as utilidad_neta'),
                \DB::raw('MIN(repases.fecha) as primera_actividad'),
                \DB::raw('MAX(repases.fecha) as ultima_actividad')
            ])
            ->leftJoin('repases', 'clinicas.id', '=', 'repases.clinica_id')
            ->leftJoin('repase_examenes', 'repases.id', '=', 'repase_examenes.repase_id')
            ->leftJoin('gastos', 'repases.id', '=', 'gastos.repase_id')
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '<=', $fecha))
            ->groupBy('clinicas.id', 'clinicas.nombre', 'clinicas.direccion', 'clinicas.telefono')
            ->orderBy('total_ingresos', 'desc');
    }

    /**
     * Scope: Análisis de capacidad máxima y saturación
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCapacitySaturation($query, array $filters = [])
    {
        $maxCapacityPerClinic = $filters['max_capacity'] ?? 1000; // Configurable

        return $query->select([
                'clinicas.id',
                'clinicas.nombre',
                \DB::raw('COUNT(DISTINCT repase_examenes.id) as current_examenes'),
                \DB::raw("{$maxCapacityPerClinic} as max_capacity"),
                \DB::raw("ROUND((COUNT(DISTINCT repase_examenes.id) * 100.0 / {$maxCapacityPerClinic}), 2) as utilization_percentage"),
                \DB::raw("({$maxCapacityPerClinic} - COUNT(DISTINCT repase_examenes.id)) as remaining_capacity"),
                \DB::raw("CASE 
                    WHEN (COUNT(DISTINCT repase_examenes.id) * 100.0 / {$maxCapacityPerClinic}) >= 90 THEN 'critical'
                    WHEN (COUNT(DISTINCT repase_examenes.id) * 100.0 / {$maxCapacityPerClinic}) >= 75 THEN 'high'
                    WHEN (COUNT(DISTINCT repase_examenes.id) * 100.0 / {$maxCapacityPerClinic}) >= 50 THEN 'normal'
                    ELSE 'low'
                END as status")
            ])
            ->leftJoin('repases', 'clinicas.id', '=', 'repases.clinica_id')
            ->leftJoin('repase_examenes', 'repases.id', '=', 'repase_examenes.repase_id')
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '<=', $fecha))
            ->groupBy('clinicas.id', 'clinicas.nombre')
            ->orderBy('utilization_percentage', 'desc');
    }

    /**
     * Scope: Clínicas con alertas de capacidad
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $threshold Umbral de alerta (default: 85%)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithCapacityAlerts($query, float $threshold = 85.0)
    {
        $maxCapacityPerClinic = 1000; // Configurable

        return $query->select([
                'clinicas.*',
                \DB::raw('COUNT(DISTINCT repase_examenes.id) as current_examenes'),
                \DB::raw("ROUND((COUNT(DISTINCT repase_examenes.id) * 100.0 / {$maxCapacityPerClinic}), 2) as utilization_percentage")
            ])
            ->leftJoin('repases', 'clinicas.id', '=', 'repases.clinica_id')
            ->leftJoin('repase_examenes', 'repases.id', '=', 'repase_examenes.repase_id')
            ->where('repases.fecha', '>=', now()->startOfMonth())
            ->where('repases.fecha', '<=', now()->endOfMonth())
            ->groupBy('clinicas.id', 'clinicas.nombre', 'clinicas.direccion', 'clinicas.telefono')
            ->havingRaw("(COUNT(DISTINCT repase_examenes.id) * 100.0 / {$maxCapacityPerClinic}) >= ?", [$threshold])
            ->orderBy('utilization_percentage', 'desc');
    }

    // ========================================
    // CAPACITY ANALYSIS HELPER METHODS
    // ========================================

    /**
     * Calcular la utilización actual de la clínica
     * 
     * @param array $filters
     * @return array
     */
    public function calculateCurrentUtilization(array $filters = []): array
    {
        $startDate = $filters['fecha_inicio'] ?? now()->startOfMonth()->format('Y-m-d');
        $endDate = $filters['fecha_fin'] ?? now()->endOfMonth()->format('Y-m-d');
        $maxCapacity = $filters['max_capacity'] ?? 1000;

        $examenes = $this->repases()
            ->whereBetween('fecha', [$startDate, $endDate])
            ->withCount('repaseExamenes')
            ->get()
            ->sum('repase_examenes_count');

        $utilizationPercentage = $maxCapacity > 0 ? ($examenes / $maxCapacity) * 100 : 0;

        return [
            'clinic_id' => $this->id,
            'clinic_name' => $this->nombre,
            'current_exams' => $examenes,
            'max_capacity' => $maxCapacity,
            'utilization_percentage' => round($utilizationPercentage, 2),
            'remaining_capacity' => max(0, $maxCapacity - $examenes),
            'status' => $this->getUtilizationStatus($utilizationPercentage),
            'period' => [
                'start' => $startDate,
                'end' => $endDate
            ]
        ];
    }

    /**
     * Calcular tendencia de crecimiento de la clínica
     * 
     * @param int $months Número de meses a analizar
     * @return array
     */
    public function calculateGrowthTrend(int $months = 12): array
    {
        $startDate = now()->subMonths($months)->format('Y-m-d');
        
        $monthlyData = $this->repases()
            ->select([
                \DB::raw("strftime('%Y-%m', fecha) as month"),
                \DB::raw('COUNT(*) as repases_count'),
                \DB::raw('SUM(total_neto) as total_ingresos')
            ])
            ->where('fecha', '>=', $startDate)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();

        if (count($monthlyData) < 2) {
            return [
                'trend' => 0,
                'direction' => 'stable',
                'confidence' => 'low',
                'months_analyzed' => count($monthlyData)
            ];
        }

        // Calcular regresión lineal simple
        $n = count($monthlyData);
        $sumX = array_sum(range(0, $n - 1));
        $sumY = array_sum(array_column($monthlyData, 'repases_count'));
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($monthlyData as $index => $data) {
            $sumXY += $index * $data['repases_count'];
            $sumX2 += $index * $index;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $trendPercentage = ($slope / (array_sum(array_column($monthlyData, 'repases_count')) / $n)) * 100;

        return [
            'trend' => round($trendPercentage, 2),
            'direction' => $slope > 0.1 ? 'growing' : ($slope < -0.1 ? 'declining' : 'stable'),
            'confidence' => $n >= 6 ? 'high' : ($n >= 3 ? 'medium' : 'low'),
            'months_analyzed' => $n,
            'monthly_data' => $monthlyData
        ];
    }

    /**
     * Detectar cuellos de botella en la clínica
     * 
     * @param array $filters
     * @return array
     */
    public function detectBottlenecks(array $filters = []): array
    {
        $utilization = $this->calculateCurrentUtilization($filters);
        $growth = $this->calculateGrowthTrend();
        $bottlenecks = [];

        // Cuello de botella por alta utilización
        if ($utilization['utilization_percentage'] >= 90) {
            $bottlenecks[] = [
                'type' => 'capacity_bottleneck',
                'severity' => 'high',
                'description' => "La clínica {$this->nombre} está operando al {$utilization['utilization_percentage']}% de su capacidad",
                'suggested_actions' => [
                    'Redistribuir exámenes a otras clínicas',
                    'Extender horarios de atención',
                    'Aumentar personal técnico'
                ]
            ];
        }

        // Cuello de botella por crecimiento acelerado
        if ($growth['trend'] > 20 && $growth['direction'] === 'growing') {
            $bottlenecks[] = [
                'type' => 'growth_bottleneck',
                'severity' => 'medium',
                'description' => "La clínica {$this->nombre} muestra un crecimiento acelerado del {$growth['trend']}% mensual",
                'suggested_actions' => [
                    'Planificar expansión de capacidad',
                    'Monitorear tendencias de demanda',
                    'Evaluar recursos adicionales'
                ]
            ];
        }

        return $bottlenecks;
    }

    /**
     * Proyectar fecha de saturación de capacidad
     * 
     * @param array $filters
     * @return array|null
     */
    public function projectSaturationDate(array $filters = []): ?array
    {
        $utilization = $this->calculateCurrentUtilization($filters);
        $growth = $this->calculateGrowthTrend();

        if ($growth['trend'] <= 0 || $growth['direction'] !== 'growing') {
            return null; // No hay crecimiento proyectable
        }

        $currentExams = $utilization['current_exams'];
        $maxCapacity = $utilization['max_capacity'];
        $monthlyGrowthRate = $growth['trend'] / 100;

        // Calcular meses hasta saturación
        $monthsToSaturation = log($maxCapacity / $currentExams) / log(1 + $monthlyGrowthRate);

        if ($monthsToSaturation <= 0 || !is_finite($monthsToSaturation)) {
            return [
                'status' => 'already_saturated',
                'message' => 'La clínica ya está en saturación o muy cerca de ella'
            ];
        }

        $saturationDate = now()->addMonths((int) ceil($monthsToSaturation));

        return [
            'status' => 'projected',
            'saturation_date' => $saturationDate->format('Y-m-d'),
            'months_to_saturation' => (int) ceil($monthsToSaturation),
            'confidence' => $growth['confidence'],
            'current_utilization' => $utilization['utilization_percentage'],
            'growth_rate' => $growth['trend']
        ];
    }

    /**
     * Obtener estado de utilización basado en porcentaje
     * 
     * @param float $utilizationPercentage
     * @return string
     */
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

    /**
     * Agregar estadísticas de capacidad a la consulta
     * 
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function addCapacityStatistics($query, array $filters = [])
    {
        $maxCapacity = $filters['max_capacity'] ?? 1000;

        return $query->addSelect([
            'current_month_exams' => function ($subQuery) use ($filters) {
                $subQuery->selectRaw('COUNT(DISTINCT repase_examenes.id)')
                    ->from('repases')
                    ->join('repase_examenes', 'repases.id', '=', 'repase_examenes.repase_id')
                    ->whereColumn('repases.clinica_id', 'clinicas.id')
                    ->where('repases.fecha', '>=', now()->startOfMonth())
                    ->where('repases.fecha', '<=', now()->endOfMonth());
            },
            'utilization_percentage' => \DB::raw("
                ROUND((
                    (SELECT COUNT(DISTINCT repase_examenes.id)
                     FROM repases 
                     JOIN repase_examenes ON repases.id = repase_examenes.repase_id
                     WHERE repases.clinica_id = clinicas.id 
                     AND repases.fecha >= '" . now()->startOfMonth()->format('Y-m-d') . "'
                     AND repases.fecha <= '" . now()->endOfMonth()->format('Y-m-d') . "'
                    ) * 100.0 / {$maxCapacity}
                ), 2)
            ")
        ]);
    }
}
