<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Repase extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'clinica_id',
        'fecha',
        'fecha_pago',
        'estado',
        'tipo_precio',
        'total_examenes',
        'total_consultas',
        'pedidos_doctor',
        'total_gastos',
        'total_neto',
        'observaciones',
        'comentarios_operativos',
        'comentarios_administrativos',
        'comentarios_caja_chica',
        'comentarios_insumios_medicos',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha' => 'date',
        'fecha_pago' => 'date',
        'total_examenes' => 'decimal:2',
        'total_consultas' => 'decimal:2',
        'total_gastos' => 'decimal:2',
        'total_neto' => 'decimal:2',
    ];

    /**
     * Relación: Un repase pertenece a una clínica.
     */
    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    /**
     * Relación: Un repase tiene muchos exámenes asociados.
     */
    public function repaseExamenes(): HasMany
    {
        return $this->hasMany(RepaseExamen::class);
    }

    /**
     * Relación: Un repase tiene muchos gastos.
     */
    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    /**
     * Scope: Filtrar repases por clínica.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param int|null $clinicaId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByClinica($query, ?int $clinicaId)
    {
        if ($clinicaId) {
            return $query->where('clinica_id', $clinicaId);
        }

        return $query;
    }

    /**
     * Scope: Filtrar repases por estado.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $estado
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByEstado($query, ?string $estado)
    {
        if ($estado) {
            return $query->where('estado', $estado);
        }

        return $query;
    }

    /**
     * Scope: Filtrar repases por rango de fechas.
     *
     * Para prevenir problemas de N+1 queries en reportes, se recomienda usar eager loading explícito:
     * Repase::with(['clinica', 'repaseExamenes.examen', 'gastos'])->byDateRange($from, $to)->get()
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string|null $from Fecha de inicio (Y-m-d)
     * @param string|null $to Fecha de fin (Y-m-d)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->where('fecha', '>=', $from);
        }

        if ($to) {
            $query->where('fecha', '<=', $to);
        }

        return $query;
    }

    /**
     * Scope: Optimizado para consultas predictivas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    /**
     * Scope: Optimizado para consultas predictivas con filtrado comprehensivo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters Filtros disponibles:
     *   - fecha_inicio: Fecha de inicio (Y-m-d)
     *   - fecha_fin: Fecha de fin (Y-m-d)
     *   - clinica_id: ID de clínica específica
     *   - estado: Estado del repase (pendiente, pagado)
     *   - tipo_precio: Tipo de precio (sin_nota, con_nota)
     *   - min_total: Total mínimo
     *   - max_total: Total máximo
     *   - include_deleted: Incluir registros eliminados (soft deletes)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPrediction($query, array $filters = [])
    {
        // Eager loading optimizado para análisis predictivo
        $query = $query->with(['clinica:id,nombre', 'repaseExamenes:repase_id,examen_id,subtotal', 'gastos:repase_id,tipo,monto']);

        // Filtros de fecha con optimización de índices
        if (isset($filters['fecha_inicio'])) {
            $query->where('fecha', '>=', $filters['fecha_inicio']);
        }

        if (isset($filters['fecha_fin'])) {
            $query->where('fecha', '<=', $filters['fecha_fin']);
        }

        // Filtro por clínica
        if (isset($filters['clinica_id'])) {
            $query->where('clinica_id', $filters['clinica_id']);
        }

        // Filtro por estado
        if (isset($filters['estado'])) {
            $query->where('estado', $filters['estado']);
        }

        // Filtro por tipo de precio
        if (isset($filters['tipo_precio'])) {
            $query->where('tipo_precio', $filters['tipo_precio']);
        }

        // Filtros por rangos de totales
        if (isset($filters['min_total'])) {
            $query->where('total_neto', '>=', $filters['min_total']);
        }

        if (isset($filters['max_total'])) {
            $query->where('total_neto', '<=', $filters['max_total']);
        }

        // Incluir registros eliminados si se especifica
        if (isset($filters['include_deleted']) && $filters['include_deleted']) {
            $query->withTrashed();
        }

        // Ordenar por fecha para análisis temporal
        return $query->orderBy('fecha');
    }

    /**
     * Scope: Agrupar por mes para análisis temporal
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
    /**
     * Scope: Agrupar por mes para análisis temporal con métricas comprehensivas
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $options Opciones de agrupación:
     *   - include_gastos: Incluir totales de gastos por tipo
     *   - include_examenes: Incluir conteo de exámenes
     *   - group_by_clinica: Agrupar también por clínica (default: true)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGroupedByMonth($query, array $options = [])
    {
        // Detectar el driver de base de datos para usar la función correcta
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        $dateFormat = $connection === 'sqlite' 
            ? "strftime('%Y-%m', fecha)" 
            : "DATE_FORMAT(fecha, '%Y-%m')";

        // Campos base de agrupación
        $selectFields = [
            "{$dateFormat} as month",
            "COUNT(*) as total_repases",
            "SUM(total_neto) as total_ingresos",
            "SUM(total_examenes) as total_examenes_monto",
            "SUM(total_consultas) as total_consultas_monto",
            "SUM(total_gastos) as total_gastos_monto",
            "AVG(total_neto) as promedio_ingresos",
            "MIN(total_neto) as min_ingresos",
            "MAX(total_neto) as max_ingresos"
        ];

        // Agrupar por clínica por defecto
        $groupBy = ['month'];
        if (!isset($options['group_by_clinica']) || $options['group_by_clinica'] !== false) {
            $selectFields[] = 'clinica_id';
            $groupBy[] = 'clinica_id';
        }

        // Incluir métricas de gastos por tipo si se solicita
        if (isset($options['include_gastos']) && $options['include_gastos']) {
            $query->leftJoin('gastos', 'repases.id', '=', 'gastos.repase_id');
            $selectFields = array_merge($selectFields, [
                "SUM(CASE WHEN gastos.tipo = 'doctor' THEN gastos.monto ELSE 0 END) as gastos_doctor",
                "SUM(CASE WHEN gastos.tipo = 'tecnico' THEN gastos.monto ELSE 0 END) as gastos_tecnico",
                "SUM(CASE WHEN gastos.tipo = 'laudos' THEN gastos.monto ELSE 0 END) as gastos_laudos",
                "SUM(CASE WHEN gastos.tipo = 'gasolina' THEN gastos.monto ELSE 0 END) as gastos_gasolina",
                "SUM(CASE WHEN gastos.tipo = 'extra' THEN gastos.monto ELSE 0 END) as gastos_extra"
            ]);
        }

        // Incluir conteo de exámenes si se solicita
        if (isset($options['include_examenes']) && $options['include_examenes']) {
            $query->leftJoin('repase_examenes', 'repases.id', '=', 'repase_examenes.repase_id');
            $selectFields[] = "COUNT(DISTINCT repase_examenes.id) as total_examenes_count";
        }

        return $query->selectRaw(implode(', ', $selectFields))
            ->groupBy($groupBy)
            ->orderBy('month');
    }
    /**
     * Scope: Optimizado para análisis de capacidad operativa
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCapacityAnalysis($query, array $filters = [])
    {
        return $query->select([
                'repases.id',
                'repases.clinica_id',
                'repases.fecha',
                'repases.total_neto',
                \DB::raw('COUNT(repase_examenes.id) as total_examenes_count'),
                \DB::raw('SUM(repase_examenes.subtotal) as total_examenes_value')
            ])
            ->join('repase_examenes', 'repases.id', '=', 'repase_examenes.repase_id')
            ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('repases.clinica_id', $id))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '<=', $fecha))
            ->groupBy('repases.id', 'repases.clinica_id', 'repases.fecha', 'repases.total_neto')
            ->orderBy('repases.fecha');
    }

    /**
     * Scope: Datos para análisis de correlación entre ingresos y gastos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForCorrelationAnalysis($query, array $filters = [])
    {
        return $query->select([
                'repases.fecha',
                'repases.clinica_id',
                'repases.total_neto as ingresos',
                \DB::raw('COALESCE(SUM(gastos.monto), 0) as gastos_totales'),
                \DB::raw('COUNT(DISTINCT gastos.id) as gastos_count')
            ])
            ->leftJoin('gastos', 'repases.id', '=', 'gastos.repase_id')
            ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('repases.clinica_id', $id))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '<=', $fecha))
            ->groupBy('repases.id', 'repases.fecha', 'repases.clinica_id', 'repases.total_neto')
            ->orderBy('repases.fecha');
    }

    /**
     * Scope: Datos agregados por período para análisis de tendencias
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $period Período de agrupación: 'day', 'week', 'month', 'quarter', 'year'
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGroupedByPeriod($query, string $period = 'month', array $filters = [])
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        // Definir formatos de fecha según el período y driver
        $dateFormats = [
            'sqlite' => [
                'day' => "strftime('%Y-%m-%d', fecha)",
                'week' => "strftime('%Y-%W', fecha)",
                'month' => "strftime('%Y-%m', fecha)",
                'quarter' => "strftime('%Y', fecha) || '-Q' || ((strftime('%m', fecha) - 1) / 3 + 1)",
                'year' => "strftime('%Y', fecha)"
            ],
            'mysql' => [
                'day' => "DATE_FORMAT(fecha, '%Y-%m-%d')",
                'week' => "DATE_FORMAT(fecha, '%Y-%u')",
                'month' => "DATE_FORMAT(fecha, '%Y-%m')",
                'quarter' => "CONCAT(YEAR(fecha), '-Q', QUARTER(fecha))",
                'year' => "YEAR(fecha)"
            ]
        ];

        $dateFormat = $dateFormats[$connection][$period] ?? $dateFormats['sqlite'][$period];

        // SQLite no tiene STDDEV, usar una aproximación o omitir
        $stddevField = $connection === 'sqlite' 
            ? "0 as desviacion_ingresos" 
            : "STDDEV(total_neto) as desviacion_ingresos";

        return $query->selectRaw("
                {$dateFormat} as period,
                COUNT(*) as total_repases,
                SUM(total_neto) as total_ingresos,
                AVG(total_neto) as promedio_ingresos,
                MIN(total_neto) as min_ingresos,
                MAX(total_neto) as max_ingresos,
                {$stddevField},
                clinica_id
            ")
            ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('clinica_id', $id))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('fecha', '<=', $fecha))
            ->groupBy('period', 'clinica_id')
            ->orderBy('period');
    }

    /**
     * Scope: Datos para análisis de estacionalidad con descomposición temporal
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForSeasonalAnalysis($query, array $filters = [])
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        if ($connection === 'sqlite') {
            $monthExtract = "CAST(strftime('%m', fecha) AS INTEGER)";
            $yearExtract = "CAST(strftime('%Y', fecha) AS INTEGER)";
            $dayOfYearExtract = "CAST(strftime('%j', fecha) AS INTEGER)";
        } else {
            $monthExtract = "MONTH(fecha)";
            $yearExtract = "YEAR(fecha)";
            $dayOfYearExtract = "DAYOFYEAR(fecha)";
        }

        return $query->selectRaw("
                fecha,
                {$monthExtract} as mes,
                {$yearExtract} as año,
                {$dayOfYearExtract} as dia_del_año,
                total_neto as valor,
                clinica_id,
                ROW_NUMBER() OVER (PARTITION BY clinica_id ORDER BY fecha) as secuencia
            ")
            ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('clinica_id', $id))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('fecha', '<=', $fecha))
            ->orderBy('clinica_id')
            ->orderBy('fecha');
    }

    /**
     * Scope: Resumen estadístico para validación de modelos predictivos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeStatisticalSummary($query, array $filters = [])
    {
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");
        
        // SQLite no tiene STDDEV ni VARIANCE, usar aproximaciones o omitir
        $stddevField = $connection === 'sqlite' 
            ? "0 as desviacion_estandar" 
            : "STDDEV(total_neto) as desviacion_estandar";
            
        $varianceField = $connection === 'sqlite' 
            ? "0 as varianza" 
            : "VARIANCE(total_neto) as varianza";

        return $query->selectRaw("
                clinica_id,
                COUNT(*) as total_registros,
                SUM(total_neto) as suma_ingresos,
                AVG(total_neto) as media_ingresos,
                MIN(total_neto) as min_ingresos,
                MAX(total_neto) as max_ingresos,
                {$stddevField},
                {$varianceField},
                MIN(fecha) as fecha_inicio,
                MAX(fecha) as fecha_fin
            ")
            ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('clinica_id', $id))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('fecha', '<=', $fecha))
            ->groupBy('clinica_id')
            ->orderBy('clinica_id');
    }

    /**
     * Obtener el total calculado (suma de exámenes - gastos)
     * Nota: total_consultas ya no se incluye en el cálculo, es solo un campo informativo.
     * 
     * @return float
     */
    public function getTotalCalculadoAttribute(): float
    {
        return $this->total_examenes - $this->total_gastos;
    }

    /**
     * Verificar si el repase tiene datos suficientes para análisis predictivo
     * 
     * @return bool
     */
    public function hasValidDataForPrediction(): bool
    {
        return $this->total_neto > 0 && 
               $this->fecha !== null && 
               $this->clinica_id !== null;
    }

    /**
     * Obtener el mes y año en formato estándar para agrupaciones
     * 
     * @return string
     */
    public function getMonthYearAttribute(): string
    {
        return $this->fecha->format('Y-m');
    }

    /**
     * Scope: Optimizado para consultas de gran volumen con paginación
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @param int $chunkSize
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForBulkAnalysis($query, array $filters = [], int $chunkSize = 1000)
    {
        return $query->select([
                'id',
                'clinica_id',
                'fecha', 
                'total_neto',
                'estado'
            ])
            ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('clinica_id', $id))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('fecha', '<=', $fecha))
            ->orderBy('fecha')
            ->orderBy('id');
    }

    /**
     * Scope: Validar integridad de datos para análisis predictivo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithValidData($query)
    {
        return $query->where('total_neto', '>', 0)
                    ->whereNotNull('fecha')
                    ->whereNotNull('clinica_id')
                    ->where('fecha', '<=', now());
    }
}
