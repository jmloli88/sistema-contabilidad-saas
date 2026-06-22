<?php

namespace App\Models;

use App\Models\Traits\ScopedByEmpresa;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    use HasFactory, ScopedByEmpresa;
    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'empresa_id',
        'repase_id',
        'tipo',
        'descripcion',
        'gasto_key',
        'monto',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'monto' => 'decimal:2',
    ];

    /**
     * Auto-derive empresa_id from the parent repase when not set explicitly.
     */
    protected static function booted(): void
    {
        static::saving(function (self $gasto) {
            if ($gasto->empresa_id === null && $gasto->repase_id !== null) {
                $gasto->empresa_id = \App\Models\Repase::withoutGlobalScope('empresa')
                    ->whereKey($gasto->repase_id)
                    ->value('empresa_id');
            }
        });
    }

    /**
     * Obtener el repase al que pertenece este gasto.
     */
    public function repase(): BelongsTo
    {
        return $this->belongsTo(Repase::class);
    }

    // ========================================
    // PREDICTIVE ANALYSIS SCOPES AND METHODS
    // ========================================

    /**
     * Scope: Optimizado para análisis predictivo de gastos
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters Filtros disponibles:
     *   - fecha_inicio: Fecha de inicio (Y-m-d)
     *   - fecha_fin: Fecha de fin (Y-m-d)
     *   - clinica_id: ID de clínica específica
     *   - tipo: Tipo de gasto específico
     *   - min_monto: Monto mínimo
     *   - max_monto: Monto máximo
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeForPredictiveAnalysis($query, array $filters = [])
    {
        // Eager loading optimizado para análisis predictivo
        $query = $query->with(['repase:id,clinica_id,fecha,total_neto']);

        // Filtros de fecha a través de la relación repase
        if (isset($filters['fecha_inicio'])) {
            $query->whereHas('repase', function ($q) use ($filters) {
                $q->where('fecha', '>=', $filters['fecha_inicio']);
            });
        }

        if (isset($filters['fecha_fin'])) {
            $query->whereHas('repase', function ($q) use ($filters) {
                $q->where('fecha', '<=', $filters['fecha_fin']);
            });
        }

        // Filtro por clínica a través de la relación repase
        if (isset($filters['clinica_id'])) {
            $query->whereHas('repase', function ($q) use ($filters) {
                $q->where('clinica_id', $filters['clinica_id']);
            });
        }

        // Filtro por tipo de gasto
        if (isset($filters['tipo'])) {
            $query->where('tipo', $filters['tipo']);
        }

        // Filtros por rangos de montos
        if (isset($filters['min_monto'])) {
            $query->where('monto', '>=', $filters['min_monto']);
        }

        if (isset($filters['max_monto'])) {
            $query->where('monto', '<=', $filters['max_monto']);
        }

        // Ordenar por fecha del repase para análisis temporal
        return $query->join('repases', 'gastos.repase_id', '=', 'repases.id')
                    ->orderBy('repases.fecha')
                    ->select('gastos.*');
    }

    /**
     * Scope: Agrupar gastos por mes para análisis temporal
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $options Opciones de agrupación:
     *   - group_by_tipo: Agrupar también por tipo de gasto (default: true)
     *   - group_by_clinica: Agrupar también por clínica (default: true)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeGroupedByMonth($query, array $options = [])
    {
        // Detectar el driver de base de datos para usar la función correcta
        $driver = config('database.default');
        $connection = config("database.connections.{$driver}.driver");

        $dateFormat = $connection === 'sqlite' 
            ? "strftime('%Y-%m', repases.fecha)" 
            : "DATE_FORMAT(repases.fecha, '%Y-%m')";

        // Campos base de agrupación
        $selectFields = [
            "{$dateFormat} as month",
            "COUNT(gastos.id) as total_gastos_count",
            "SUM(gastos.monto) as total_gastos_monto",
            "AVG(gastos.monto) as promedio_gasto",
            "MIN(gastos.monto) as min_gasto",
            "MAX(gastos.monto) as max_gasto"
        ];

        // Agrupar por mes
        $groupBy = ['month'];

        // Agrupar por tipo de gasto por defecto
        if (!isset($options['group_by_tipo']) || $options['group_by_tipo'] !== false) {
            $selectFields[] = 'gastos.tipo';
            $groupBy[] = 'gastos.tipo';
        }

        // Agrupar por clínica por defecto
        if (!isset($options['group_by_clinica']) || $options['group_by_clinica'] !== false) {
            $selectFields[] = 'repases.clinica_id';
            $groupBy[] = 'repases.clinica_id';
        }

        return $query->join('repases', 'gastos.repase_id', '=', 'repases.id')
            ->selectRaw(implode(', ', $selectFields))
            ->groupBy($groupBy)
            ->orderBy('month');
    }

    /**
     * Scope: Análisis de correlación entre gastos e ingresos
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
                'gastos.tipo',
                'gastos.monto as gasto_monto',
                'repases.total_neto as ingreso_total'
            ])
            ->join('repases', 'gastos.repase_id', '=', 'repases.id')
            ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('repases.clinica_id', $id))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '<=', $fecha))
            ->when($filters['tipo'] ?? null, fn($q, $tipo) => $q->where('gastos.tipo', $tipo))
            ->orderBy('repases.fecha');
    }

    /**
     * Scope: Análisis de tendencias por categoría de gasto
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

        // SQLite no tiene STDDEV, usar una aproximación o omitir
        $stddevField = $connection === 'sqlite' 
            ? "0 as desviacion_gastos" 
            : "STDDEV(gastos.monto) as desviacion_gastos";

        return $query->join('repases', 'gastos.repase_id', '=', 'repases.id')
            ->selectRaw("
                {$dateFormat} as period,
                gastos.tipo,
                repases.clinica_id,
                COUNT(gastos.id) as total_gastos_count,
                SUM(gastos.monto) as total_gastos_monto,
                AVG(gastos.monto) as promedio_gastos,
                MIN(gastos.monto) as min_gastos,
                MAX(gastos.monto) as max_gastos,
                {$stddevField}
            ")
            ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('repases.clinica_id', $id))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '<=', $fecha))
            ->when($filters['tipo'] ?? null, fn($q, $tipo) => $q->where('gastos.tipo', $tipo))
            ->groupBy('period', 'gastos.tipo', 'repases.clinica_id')
            ->orderBy('period');
    }

    /**
     * Scope: Resumen estadístico por tipo de gasto para validación de modelos
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
            : "STDDEV(gastos.monto) as desviacion_estandar";
            
        $varianceField = $connection === 'sqlite' 
            ? "0 as varianza" 
            : "VARIANCE(gastos.monto) as varianza";

        return $query->join('repases', 'gastos.repase_id', '=', 'repases.id')
            ->selectRaw("
                gastos.tipo,
                repases.clinica_id,
                COUNT(gastos.id) as total_registros,
                SUM(gastos.monto) as suma_gastos,
                AVG(gastos.monto) as media_gastos,
                MIN(gastos.monto) as min_gastos,
                MAX(gastos.monto) as max_gastos,
                {$stddevField},
                {$varianceField},
                MIN(repases.fecha) as fecha_inicio,
                MAX(repases.fecha) as fecha_fin
            ")
            ->when($filters['clinica_id'] ?? null, fn($q, $id) => $q->where('repases.clinica_id', $id))
            ->when($filters['fecha_inicio'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '>=', $fecha))
            ->when($filters['fecha_fin'] ?? null, fn($q, $fecha) => $q->where('repases.fecha', '<=', $fecha))
            ->when($filters['tipo'] ?? null, fn($q, $tipo) => $q->where('gastos.tipo', $tipo))
            ->groupBy('gastos.tipo', 'repases.clinica_id')
            ->orderBy('gastos.tipo', 'repases.clinica_id');
    }

    /**
     * Scope: Validar integridad de datos para análisis predictivo
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWithValidData($query)
    {
        return $query->where('monto', '>', 0)
                    ->whereNotNull('tipo')
                    ->whereNotNull('repase_id')
                    ->whereHas('repase', function ($q) {
                        $q->whereNotNull('fecha')
                          ->where('fecha', '<=', now());
                    });
    }

    // ========================================
    // PREDICTIVE ANALYSIS HELPER METHODS
    // ========================================

    /**
     * Categorizar gasto para análisis predictivo
     * 
     * @return string
     */
    public function getCategoryAttribute(): string
    {
        return match($this->tipo) {
            'doctor', 'tecnico' => 'personal',
            'laudos' => 'suministros', 
            'gasolina' => 'otros',
            'extra' => 'otros',
            default => 'otros'
        };
    }

    /**
     * Verificar si el gasto tiene datos válidos para análisis predictivo
     * 
     * @return bool
     */
    public function hasValidDataForPrediction(): bool
    {
        return $this->monto > 0 && 
               $this->tipo !== null && 
               $this->repase_id !== null &&
               $this->repase !== null;
    }

    /**
     * Obtener el mes y año del gasto en formato estándar
     * 
     * @return string|null
     */
    public function getMonthYearAttribute(): ?string
    {
        return $this->repase?->fecha?->format('Y-m');
    }

    /**
     * Obtener el porcentaje del gasto respecto al ingreso total del repase
     * 
     * @return float
     */
    public function getPercentageOfIncomeAttribute(): float
    {
        if (!$this->repase || $this->repase->total_neto <= 0) {
            return 0.0;
        }
        
        return ($this->monto / $this->repase->total_neto) * 100;
    }

    /**
     * Scope: Gastos que exceden un porcentaje del ingreso
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $percentage
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeExceedingIncomePercentage($query, float $percentage = 10.0)
    {
        return $query->join('repases', 'gastos.repase_id', '=', 'repases.id')
            ->whereRaw('(gastos.monto / repases.total_neto) * 100 > ?', [$percentage])
            ->select('gastos.*');
    }

    /**
     * Scope: Gastos anómalos (outliers) para detección de irregularidades
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param float $stdDevMultiplier Multiplicador de desviación estándar (default: 2)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOutliers($query, float $stdDevMultiplier = 2.0)
    {
        // Subconsulta para calcular estadísticas por tipo
        $statsSubquery = static::selectRaw('
                tipo,
                AVG(monto) as avg_monto,
                CASE 
                    WHEN COUNT(*) > 1 THEN 
                        SQRT(SUM((monto - (SELECT AVG(monto) FROM gastos g2 WHERE g2.tipo = gastos.tipo)) * (monto - (SELECT AVG(monto) FROM gastos g3 WHERE g3.tipo = gastos.tipo))) / (COUNT(*) - 1))
                    ELSE 0 
                END as std_dev
            ')
            ->groupBy('tipo');

        return $query->joinSub($statsSubquery, 'stats', function ($join) {
                $join->on('gastos.tipo', '=', 'stats.tipo');
            })
            ->whereRaw('ABS(gastos.monto - stats.avg_monto) > (stats.std_dev * ?)', [$stdDevMultiplier])
            ->select('gastos.*', 'stats.avg_monto', 'stats.std_dev');
    }
}
