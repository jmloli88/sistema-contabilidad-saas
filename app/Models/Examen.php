<?php

namespace App\Models;

use App\Models\Traits\ScopedByEmpresa;
use App\Support\EmpresaContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Examen extends Model
{
    use HasFactory, ScopedByEmpresa;

    /**
     * El nombre de la tabla asociada al modelo.
     *
     * @var string
     */
    protected $table = 'examenes';

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'nombre',
        'precio_sin_nota',
        'precio_con_nota',
        'empresa_id',
        'is_active',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'precio_sin_nota' => 'decimal:2',
        'precio_con_nota' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Obtener los registros de repase_examenes asociados a este examen.
     */
    public function repaseExamenes(): HasMany
    {
        return $this->hasMany(RepaseExamen::class);
    }

    /**
     * Clínicas que tienen precios personalizados para este examen.
     */
    public function clinicas(): BelongsToMany
    {
        return $this->belongsToMany(Clinica::class, 'clinica_examen')
            ->withPivot(['precio_sin_nota', 'precio_con_nota'])
            ->withTimestamps();
    }

    /**
     * Resolver el precio de un examen para una clínica específica.
     *
     * Two-tier resolution: si la clínica tiene un precio personalizado
     * no nulo en el pivote, se usa ese. De lo contrario, se usa el
     * precio global del examen.
     *
     * @param  int|null  $clinicaId
     * @param  string    $tipoPrecio 'sin_nota' o 'con_nota'
     * @return float
     */
    public function getPrecioParaClinica(?int $clinicaId, string $tipoPrecio): float
    {
        if ($clinicaId === null) {
            return (float) $this->{"precio_{$tipoPrecio}"};
        }

        $clinica = $this->clinicas()
            ->where('clinica_id', $clinicaId)
            ->wherePivotNotNull("precio_{$tipoPrecio}")
            ->first();

        if ($clinica && $clinica->pivot->{"precio_{$tipoPrecio}"} !== null) {
            return (float) $clinica->pivot->{"precio_{$tipoPrecio}"};
        }

        return (float) $this->{"precio_{$tipoPrecio}"};
    }

    /**
     * Obtener ambos precios (sin_nota y con_nota) para una clínica.
     *
     * @param  int|null  $clinicaId
     * @return array<string, float>
     */
    public function getPreciosParaClinica(?int $clinicaId): array
    {
        return [
            'sin_nota' => $this->getPrecioParaClinica($clinicaId, 'sin_nota'),
            'con_nota' => $this->getPrecioParaClinica($clinicaId, 'con_nota'),
        ];
    }

    // ========================================
    // ACTIVE EXAM SCOPE
    // ========================================

    /**
     * Scope: Filter to only active exams.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the 7 default exam templates for new empresas.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function defaults(): array
    {
        return [
            [
                'nombre' => 'Electroencefalograma c/ mapeamento 3d + foto estimulo',
                'precio_sin_nota' => 200.00,
                'precio_con_nota' => 220.00,
            ],
            [
                'nombre' => 'Electroencefalograma c/ mapa',
                'precio_sin_nota' => 120.00,
                'precio_con_nota' => 140.00,
            ],
            [
                'nombre' => 'Electroencefalograma',
                'precio_sin_nota' => 100.00,
                'precio_con_nota' => 120.00,
            ],
            [
                'nombre' => 'Electroneuromiografia MEMBROS unilateral',
                'precio_sin_nota' => 150.00,
                'precio_con_nota' => 180.00,
            ],
            [
                'nombre' => 'Electroneuromiografia FACIAL unilateral',
                'precio_sin_nota' => 170.00,
                'precio_con_nota' => 200.00,
            ],
            [
                'nombre' => 'Potencial evocado VISUAL unilateral',
                'precio_sin_nota' => 146.00,
                'precio_con_nota' => 166.00,
            ],
            [
                'nombre' => 'Potencial evocado AUDITIVO unilateral',
                'precio_sin_nota' => 146.00,
                'precio_con_nota' => 166.00,
            ],
        ];
    }

    // ========================================
    // UTILIZATION TRACKING EXTENSIONS
    // ========================================

    /**
     * Scope: Optimizado para análisis de utilización de exámenes
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters Filtros disponibles:
     *   - fecha_inicio: Fecha de inicio (Y-m-d)
     *   - fecha_fin: Fecha de fin (Y-m-d)
     *   - clinica_id: ID de clínica específica
     *   - tipo_precio: Tipo de precio (sin_nota, con_nota)
     *   - min_precio: Precio mínimo
     *   - max_precio: Precio máximo
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForUtilizationAnalysis($query, array $filters = [])
    {
        // Eager loading optimizado para análisis de utilización
        $query = $query->with(['repaseExamenes' => function ($q) use ($filters) {
            $q->select('id', 'examen_id', 'repase_id', 'subtotal', 'tipo_precio')
              ->with(['repase:id,clinica_id,fecha,total_neto']);

            // Filtros a través de la relación repase
            if (isset($filters['fecha_inicio']) || isset($filters['fecha_fin']) || isset($filters['clinica_id'])) {
                $q->whereHas('repase', function ($subQ) use ($filters) {
                    $subQ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('fecha', '>=', $fecha))
                         ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('fecha', '<=', $fecha))
                         ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('clinica_id', $id));
                });
            }

            // Filtro por tipo de precio (a través de repase)
            if (isset($filters['tipo_precio'])) {
                $q->whereHas('repase', function ($subQ) use ($filters) {
                    $subQ->where('tipo_precio', $filters['tipo_precio']);
                });
            }
        }]);

        // Filtros por rangos de precios
        if (isset($filters['min_precio'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('precio_sin_nota', '>=', $filters['min_precio'])
                  ->orWhere('precio_con_nota', '>=', $filters['min_precio']);
            });
        }

        if (isset($filters['max_precio'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('precio_sin_nota', '<=', $filters['max_precio'])
                  ->orWhere('precio_con_nota', '<=', $filters['max_precio']);
            });
        }

        return $query->orderBy('nombre');
    }

    /**
     * Scope: Estadísticas de utilización por examen
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUtilizationStats($query, array $filters = [])
    {
        return $query->select([
                'examenes.id',
                'examenes.nombre',
                'examenes.precio_sin_nota',
                'examenes.precio_con_nota',
                \DB::raw('COUNT(DISTINCT repase_examenes.id) as total_realizados'),
                \DB::raw('COUNT(DISTINCT repases.clinica_id) as clinicas_utilizan'),
                \DB::raw('SUM(repase_examenes.subtotal) as ingresos_totales'),
                \DB::raw('AVG(repase_examenes.subtotal) as precio_promedio'),
                \DB::raw('MIN(repases.fecha) as primera_utilizacion'),
                \DB::raw('MAX(repases.fecha) as ultima_utilizacion'),
                \DB::raw('COUNT(DISTINCT DATE(repases.fecha)) as dias_activos')
            ])
            ->leftJoin('repase_examenes', 'examenes.id', '=', 'repase_examenes.examen_id')
            ->leftJoin('repases', 'repase_examenes.repase_id', '=', 'repases.id')
            ->join('clinicas', 'repases.clinica_id', '=', 'clinicas.id')
            ->when(EmpresaContext::isSet(), fn($q) => $q->where('clinicas.empresa_id', EmpresaContext::get()))
            ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('repases.clinica_id', $id))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '<=', $fecha))
            ->when($filters['tipo_precio'] ?? null, fn($q, $tipo) => $q->where('repase_examenes.tipo_precio', $tipo))
            ->groupBy('examenes.id', 'examenes.nombre', 'examenes.precio_sin_nota', 'examenes.precio_con_nota')
            ->orderBy('total_realizados', 'desc');
    }

    /**
     * Scope: Análisis de tendencias de utilización por período
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $period Período de agrupación: 'day', 'week', 'month', 'quarter', 'year'
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUtilizationTrends($query, string $period = 'month', array $filters = [])
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        // Definir formatos de fecha según el período y driver
        $dateFormats = [
            'sqlite' => [
                'day' => "strftime('%Y-%m-%d', repases.fecha)",
                'week' => "strftime('%Y-%W', repases.fecha)",
                'month' => "strftime('%Y-%m', repases.fecha)",
                'quarter' => "strftime('%Y', repases.fecha) || '-Q' || ((strftime('%m', repases.fecha) - 1) / 3 + 1)",
                'year' => "strftime('%Y', repases.fecha)"
            ],
            'mysql' => [
                'day' => "DATE_FORMAT(repases.fecha, '%Y-%m-%d')",
                'week' => "DATE_FORMAT(repases.fecha, '%Y-%u')",
                'month' => "DATE_FORMAT(repases.fecha, '%Y-%m')",
                'quarter' => "CONCAT(YEAR(repases.fecha), '-Q', QUARTER(repases.fecha))",
                'year' => "YEAR(repases.fecha)"
            ]
        ];

        $dateFormat = $dateFormats[$connection][$period] ?? $dateFormats['sqlite'][$period];

        return $query->select([
                'examenes.id',
                'examenes.nombre',
                \DB::raw("{$dateFormat} as period"),
                \DB::raw('COUNT(repase_examenes.id) as utilizaciones'),
                \DB::raw('SUM(repase_examenes.subtotal) as ingresos_periodo'),
                \DB::raw('AVG(repase_examenes.subtotal) as precio_promedio_periodo'),
                \DB::raw('COUNT(DISTINCT repases.clinica_id) as clinicas_periodo')
            ])
            ->leftJoin('repase_examenes', 'examenes.id', '=', 'repase_examenes.examen_id')
            ->leftJoin('repases', 'repase_examenes.repase_id', '=', 'repases.id')
            ->join('clinicas', 'repases.clinica_id', '=', 'clinicas.id')
            ->when(EmpresaContext::isSet(), fn($q) => $q->where('clinicas.empresa_id', EmpresaContext::get()))
            ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('repases.clinica_id', $id))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '<=', $fecha))
            ->when($filters['tipo_precio'] ?? null, fn($q, $tipo) => $q->where('repase_examenes.tipo_precio', $tipo))
            ->groupBy('examenes.id', 'examenes.nombre', 'period')
            ->orderBy('examenes.nombre')
            ->orderBy('period');
    }

    /**
     * Scope: Exámenes más populares por clínica
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePopularityByClinic($query, array $filters = [])
    {
        return $query->select([
                'examenes.id',
                'examenes.nombre',
                'repases.clinica_id',
                'clinicas.nombre as clinica_nombre',
                \DB::raw('COUNT(repase_examenes.id) as total_utilizaciones'),
                \DB::raw('SUM(repase_examenes.subtotal) as ingresos_totales'),
                \DB::raw('AVG(repase_examenes.subtotal) as precio_promedio'),
                \DB::raw('RANK() OVER (PARTITION BY repases.clinica_id ORDER BY COUNT(repase_examenes.id) DESC) as ranking_clinica')
            ])
            ->join('repase_examenes', 'examenes.id', '=', 'repase_examenes.examen_id')
            ->join('repases', 'repase_examenes.repase_id', '=', 'repases.id')
            ->join('clinicas', 'repases.clinica_id', '=', 'clinicas.id')
            ->when(EmpresaContext::isSet(), fn($q) => $q->where('clinicas.empresa_id', EmpresaContext::get()))
            ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('repases.clinica_id', $id))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '<=', $fecha))
            ->when($filters['top_n'] ?? null, fn($q, $n) => $q->havingRaw('ranking_clinica <= ?', [$n]))
            ->groupBy('examenes.id', 'examenes.nombre', 'repases.clinica_id', 'clinicas.nombre')
            ->orderBy('repases.clinica_id')
            ->orderBy('total_utilizaciones', 'desc');
    }

    /**
     * Scope: Análisis de rentabilidad por examen
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeProfitabilityAnalysis($query, array $filters = [])
    {
        return $query->select([
                'examenes.id',
                'examenes.nombre',
                'examenes.precio_sin_nota',
                'examenes.precio_con_nota',
                \DB::raw('COUNT(repase_examenes.id) as total_realizados'),
                \DB::raw('SUM(repase_examenes.subtotal) as ingresos_totales'),
                \DB::raw('AVG(repase_examenes.subtotal) as precio_promedio_real'),
                \DB::raw('SUM(CASE WHEN repases.tipo_precio = "sin_nota" THEN 1 ELSE 0 END) as realizados_sin_nota'),
                \DB::raw('SUM(CASE WHEN repases.tipo_precio = "con_nota" THEN 1 ELSE 0 END) as realizados_con_nota'),
                \DB::raw('(SUM(repase_examenes.subtotal) / COUNT(repase_examenes.id)) / ((examenes.precio_sin_nota + examenes.precio_con_nota) / 2) * 100 as eficiencia_precio'),
                \DB::raw('SUM(repase_examenes.subtotal) / COUNT(DISTINCT repases.clinica_id) as ingreso_promedio_por_clinica')
            ])
            ->leftJoin('repase_examenes', 'examenes.id', '=', 'repase_examenes.examen_id')
            ->leftJoin('repases', 'repase_examenes.repase_id', '=', 'repases.id')
            ->join('clinicas', 'repases.clinica_id', '=', 'clinicas.id')
            ->when(EmpresaContext::isSet(), fn($q) => $q->where('clinicas.empresa_id', EmpresaContext::get()))
            ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('repases.clinica_id', $id))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '<=', $fecha))
            ->groupBy('examenes.id', 'examenes.nombre', 'examenes.precio_sin_nota', 'examenes.precio_con_nota')
            ->orderBy('ingresos_totales', 'desc');
    }

    /**
     * Scope: Exámenes con baja utilización (candidatos para revisión)
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int $minUtilizations Mínimo de utilizaciones esperadas
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLowUtilization($query, int $minUtilizations = 5, array $filters = [])
    {
        return $query->select([
                'examenes.*',
                \DB::raw('COALESCE(COUNT(repase_examenes.id), 0) as total_utilizaciones'),
                \DB::raw('COALESCE(SUM(repase_examenes.subtotal), 0) as ingresos_totales'),
                \DB::raw('COALESCE(MAX(repases.fecha), "Nunca") as ultima_utilizacion')
            ])
            ->leftJoin('repase_examenes', 'examenes.id', '=', 'repase_examenes.examen_id')
            ->leftJoin('repases', 'repase_examenes.repase_id', '=', 'repases.id')
            ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('repases.clinica_id', $id))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '<=', $fecha))
            ->groupBy('examenes.id', 'examenes.nombre', 'examenes.precio_sin_nota', 'examenes.precio_con_nota')
            ->havingRaw('COALESCE(COUNT(repase_examenes.id), 0) < ?', [$minUtilizations])
            ->orderBy('total_utilizaciones', 'asc');
    }

    /**
     * Scope: Validar integridad de datos para análisis predictivo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithValidData($query)
    {
        return $query->where('precio_sin_nota', '>', 0)
                    ->where('precio_con_nota', '>', 0)
                    ->whereNotNull('nombre')
                    ->where('nombre', '!=', '');
    }

    // ========================================
    // UTILIZATION TRACKING HELPER METHODS
    // ========================================

    /**
     * Calcular estadísticas de utilización del examen
     *
     * @param array $filters
     * @return array
     */
    public function calculateUtilizationStats(array $filters = []): array
    {
        $startDate = $filters['fecha_inicio'] ?? now()->subMonths(12)->format('Y-m-d');
        $endDate = $filters['fecha_fin'] ?? now()->format('Y-m-d');

        $stats = $this->repaseExamenes()
            ->join('repases', 'repase_examenes.repase_id', '=', 'repases.id')
            ->where('repases.fecha', '>=', $startDate)
            ->where('repases.fecha', '<=', $endDate)
            ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('repases.clinica_id', $id))
            ->selectRaw('
                COUNT(repase_examenes.id) as total_utilizaciones,
                COUNT(DISTINCT repase_examenes.repase_id) as repases_distintos,
                SUM(repase_examenes.subtotal) as ingresos_totales,
                AVG(repase_examenes.subtotal) as precio_promedio,
                MIN(repase_examenes.subtotal) as precio_minimo,
                MAX(repase_examenes.subtotal) as precio_maximo,
                SUM(CASE WHEN repases.tipo_precio = "sin_nota" THEN 1 ELSE 0 END) as utilizaciones_sin_nota,
                SUM(CASE WHEN repases.tipo_precio = "con_nota" THEN 1 ELSE 0 END) as utilizaciones_con_nota
            ')
            ->first();

        // Calcular clínicas que utilizan este examen
        $clinicasUtilizan = $this->repaseExamenes()
            ->whereHas('repase', function ($q) use ($filters, $startDate, $endDate) {
                $q->whereBetween('fecha', [$startDate, $endDate])
                  ->when($filters['clinica_id'] ?? null, fn($subQ, $id) => $subQ->where('clinica_id', $id));
            })
            ->join('repases', 'repase_examenes.repase_id', '=', 'repases.id')
            ->distinct('repases.clinica_id')
            ->count();

        return [
            'examen_id' => $this->id,
            'examen_nombre' => $this->nombre,
            'total_utilizaciones' => $stats->total_utilizaciones ?? 0,
            'repases_distintos' => $stats->repases_distintos ?? 0,
            'clinicas_utilizan' => $clinicasUtilizan,
            'ingresos_totales' => (float) ($stats->ingresos_totales ?? 0),
            'precio_promedio' => (float) ($stats->precio_promedio ?? 0),
            'precio_minimo' => (float) ($stats->precio_minimo ?? 0),
            'precio_maximo' => (float) ($stats->precio_maximo ?? 0),
            'utilizaciones_sin_nota' => $stats->utilizaciones_sin_nota ?? 0,
            'utilizaciones_con_nota' => $stats->utilizaciones_con_nota ?? 0,
            'eficiencia_precio' => $this->calculatePriceEfficiency($stats),
            'periodo_analisis' => [
                'inicio' => $startDate,
                'fin' => $endDate
            ]
        ];
    }

    /**
     * Calcular tendencia de utilización del examen
     *
     * @param int $months Número de meses a analizar
     * @return array
     */
    public function calculateUtilizationTrend(int $months = 12): array
    {
        $startDate = now()->subMonths($months)->format('Y-m-d');

        $monthlyData = $this->repaseExamenes()
            ->join('repases', 'repase_examenes.repase_id', '=', 'repases.id')
            ->select([
                \DB::raw("strftime('%Y-%m', repases.fecha) as month"),
                \DB::raw('COUNT(repase_examenes.id) as utilizaciones'),
                \DB::raw('SUM(repase_examenes.subtotal) as ingresos'),
                \DB::raw('AVG(repase_examenes.subtotal) as precio_promedio')
            ])
            ->where('repases.fecha', '>=', $startDate)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();

        if (count($monthlyData) < 2) {
            return [
                'trend' => 0,
                'direction' => 'stable',
                'confidence' => 'low',
                'months_analyzed' => count($monthlyData),
                'monthly_data' => $monthlyData
            ];
        }

        // Calcular regresión lineal simple para utilizaciones
        $n = count($monthlyData);
        $sumX = array_sum(range(0, $n - 1));
        $sumY = array_sum(array_column($monthlyData, 'utilizaciones'));
        $sumXY = 0;
        $sumX2 = 0;

        foreach ($monthlyData as $index => $data) {
            $sumXY += $index * $data['utilizaciones'];
            $sumX2 += $index * $index;
        }

        $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumX2 - $sumX * $sumX);
        $avgUtilizations = $sumY / $n;
        $trendPercentage = $avgUtilizations > 0 ? ($slope / $avgUtilizations) * 100 : 0;

        return [
            'trend' => round($trendPercentage, 2),
            'direction' => $slope > 0.1 ? 'growing' : ($slope < -0.1 ? 'declining' : 'stable'),
            'confidence' => $n >= 6 ? 'high' : ($n >= 3 ? 'medium' : 'low'),
            'months_analyzed' => $n,
            'average_monthly_utilizations' => round($avgUtilizations, 2),
            'monthly_data' => $monthlyData
        ];
    }

    /**
     * Obtener ranking de popularidad del examen
     *
     * @param array $filters
     * @return array
     */
    public function getPopularityRanking(array $filters = []): array
    {
        $startDate = $filters['fecha_inicio'] ?? now()->subMonths(12)->format('Y-m-d');
        $endDate = $filters['fecha_fin'] ?? now()->format('Y-m-d');

        // Obtener ranking general
        $generalRanking = static::select([
                'examenes.id',
                'examenes.nombre',
                \DB::raw('COUNT(repase_examenes.id) as total_utilizaciones')
            ])
            ->leftJoin('repase_examenes', 'examenes.id', '=', 'repase_examenes.examen_id')
            ->leftJoin('repases', 'repase_examenes.repase_id', '=', 'repases.id')
            ->whereBetween('repases.fecha', [$startDate, $endDate])
            ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('repases.clinica_id', $id))
            ->groupBy('examenes.id', 'examenes.nombre')
            ->orderBy('total_utilizaciones', 'desc')
            ->get()
            ->pluck('total_utilizaciones', 'id')
            ->toArray();

        $totalExamenes = count($generalRanking);
        $currentPosition = array_search($this->id, array_keys($generalRanking)) + 1;
        $currentUtilizations = $generalRanking[$this->id] ?? 0;

        return [
            'examen_id' => $this->id,
            'examen_nombre' => $this->nombre,
            'posicion_ranking' => $currentPosition,
            'total_examenes' => $totalExamenes,
            'percentil' => $totalExamenes > 0 ? round((($totalExamenes - $currentPosition + 1) / $totalExamenes) * 100, 1) : 0,
            'utilizaciones' => $currentUtilizations,
            'categoria_popularidad' => $this->getPopularityCategory($currentPosition, $totalExamenes),
            'periodo_analisis' => [
                'inicio' => $startDate,
                'fin' => $endDate
            ]
        ];
    }

    /**
     * Detectar anomalías en la utilización del examen
     *
     * @param array $filters
     * @return array
     */
    public function detectUtilizationAnomalies(array $filters = []): array
    {
        $trend = $this->calculateUtilizationTrend();
        $stats = $this->calculateUtilizationStats($filters);
        $anomalies = [];

        // Anomalía: Caída drástica en utilización
        if ($trend['direction'] === 'declining' && abs($trend['trend']) > 30) {
            $anomalies[] = [
                'type' => 'utilization_decline',
                'severity' => 'high',
                'description' => "El examen {$this->nombre} muestra una caída del {$trend['trend']}% en su utilización",
                'suggested_actions' => [
                    'Revisar relevancia clínica del examen',
                    'Evaluar competencia con otros exámenes',
                    'Considerar ajuste de precios'
                ]
            ];
        }

        // Anomalía: Precio muy por debajo del promedio
        if ($stats['precio_promedio'] > 0) {
            $expectedPrice = ($this->precio_sin_nota + $this->precio_con_nota) / 2;
            $priceEfficiency = ($stats['precio_promedio'] / $expectedPrice) * 100;

            if ($priceEfficiency < 70) {
                $anomalies[] = [
                    'type' => 'price_efficiency',
                    'severity' => 'medium',
                    'description' => "El examen {$this->nombre} tiene una eficiencia de precio del {$priceEfficiency}%",
                    'suggested_actions' => [
                        'Revisar política de precios',
                        'Analizar descuentos aplicados',
                        'Evaluar estructura de costos'
                    ]
                ];
            }
        }

        // Anomalía: Baja utilización general
        if ($stats['total_utilizaciones'] < 5 && $trend['months_analyzed'] >= 6) {
            $anomalies[] = [
                'type' => 'low_utilization',
                'severity' => 'medium',
                'description' => "El examen {$this->nombre} tiene muy baja utilización ({$stats['total_utilizaciones']} veces en {$trend['months_analyzed']} meses)",
                'suggested_actions' => [
                    'Evaluar discontinuación del examen',
                    'Revisar necesidad clínica',
                    'Considerar promoción o capacitación'
                ]
            ];
        }

        return $anomalies;
    }

    /**
     * Calcular eficiencia de precio del examen
     *
     * @param object $stats
     * @return float
     */
    private function calculatePriceEfficiency($stats): float
    {
        if (!$stats || !$stats->precio_promedio) {
            return 0.0;
        }

        $expectedPrice = ($this->precio_sin_nota + $this->precio_con_nota) / 2;

        if ($expectedPrice <= 0) {
            return 0.0;
        }

        return round(($stats->precio_promedio / $expectedPrice) * 100, 2);
    }

    /**
     * Obtener categoría de popularidad basada en ranking
     *
     * @param int $position
     * @param int $total
     * @return string
     */
    private function getPopularityCategory(int $position, int $total): string
    {
        if ($total === 0) return 'unknown';

        $percentile = (($total - $position + 1) / $total) * 100;

        if ($percentile >= 90) return 'muy_popular';
        if ($percentile >= 70) return 'popular';
        if ($percentile >= 50) return 'moderado';
        if ($percentile >= 30) return 'bajo';
        return 'muy_bajo';
    }
}
