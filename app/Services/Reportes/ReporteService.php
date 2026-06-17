<?php

namespace App\Services\Reportes;

use App\Models\Repase;
use App\Models\Clinica;
use App\Models\Examen;
use App\Models\RepaseExamen;
use App\Support\EmpresaContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para cálculos de reportes avanzados
 * 
 * Este servicio encapsula toda la lógica de negocio para generar reportes
 * de rentabilidad, productividad y comparativos. Utiliza agregaciones SQL
 * para optimizar el rendimiento.
 */
class ReporteService
{
    /**
     * Calcula rentabilidad por clínica
     * 
     * Genera un reporte con métricas financieras agregadas por clínica:
     * - total_ingresos: suma de total_examenes
     * - total_gastos: suma de gastos
     * - ganancia_neta: ingresos - gastos
     * - margen_ganancia: ((ingresos - gastos) / ingresos) * 100
     * - cantidad_repases: conteo de repases
     * 
     * @param array $filtros ['fecha_inicio', 'fecha_fin', 'clinica_id']
     * @return \Illuminate\Support\Collection
     */
    public function calcularRentabilidadClinica(array $filtros): Collection
    {
        // Obtener IDs de repases filtrados
        $repasesIds = Repase::query()
            ->byDateRange($filtros['fecha_inicio'] ?? null, $filtros['fecha_fin'] ?? null)
            ->pluck('id');

        // Si no hay repases, retornar colección vacía de clínicas con valores en cero
        if ($repasesIds->isEmpty()) {
            $query = Clinica::query();
            
            if (isset($filtros['clinica_id']) && $filtros['clinica_id']) {
                $query->where('id', $filtros['clinica_id']);
            }
            
            $query->when(EmpresaContext::isSet(), fn($q) => $q->where('clinicas.empresa_id', EmpresaContext::get()));
            
            return $query->get()->map(function ($clinica) {
                return (object) [
                    'clinica_id' => $clinica->id,
                    'nombre_clinica' => $clinica->nombre,
                    'total_ingresos' => 0,
                    'total_gastos' => 0,
                    'ganancia_neta' => 0,
                    'cantidad_repases' => 0,
                    'margen_ganancia' => null,
                ];
            });
        }

        $query = Clinica::query()
            ->select([
                'clinicas.id as clinica_id',
                'clinicas.nombre as nombre_clinica',
                DB::raw('COALESCE(SUM(repases.total_examenes), 0) as total_ingresos'),
                DB::raw('COALESCE(SUM(repases.total_gastos), 0) as total_gastos'),
                DB::raw('COALESCE(SUM(repases.total_neto), 0) as ganancia_neta'),
                DB::raw('COUNT(repases.id) as cantidad_repases'),
            ])
            ->leftJoin('repases', function ($join) use ($repasesIds) {
                $join->on('clinicas.id', '=', 'repases.clinica_id')
                    ->whereIn('repases.id', $repasesIds)
                    ->whereNull('repases.deleted_at');
            })
            ->when(EmpresaContext::isSet(), fn($q) => $q->where('clinicas.empresa_id', EmpresaContext::get()))
            ->groupBy('clinicas.id', 'clinicas.nombre');

        // Filtrar por clínica específica si se proporciona
        if (isset($filtros['clinica_id']) && $filtros['clinica_id']) {
            $query->where('clinicas.id', $filtros['clinica_id']);
        }

        $resultados = $query->orderBy('ganancia_neta', 'desc')->get();

        // Calcular margen de ganancia para cada registro
        return $resultados->map(function ($registro) {
            $registro->margen_ganancia = $this->calcularMargenGanancia(
                (float) $registro->total_ingresos,
                (float) $registro->total_gastos
            );
            
            return $registro;
        });
    }

    /**
     * Calcula rentabilidad por tipo de examen
     * 
     * Genera un reporte con métricas de ingresos por tipo de examen:
     * - cantidad_total: suma de cantidades realizadas
     * - total_ingresos: suma de subtotales
     * - ingreso_promedio: total_ingresos / cantidad_total
     * 
     * @param array $filtros ['fecha_inicio', 'fecha_fin', 'clinica_id', 'examen_id']
     * @return \Illuminate\Support\Collection
     */
    public function calcularRentabilidadExamen(array $filtros): Collection
    {
        // Obtener IDs de repases filtrados
        $repasesIds = Repase::query()
            ->byDateRange($filtros['fecha_inicio'] ?? null, $filtros['fecha_fin'] ?? null)
            ->byClinica($filtros['clinica_id'] ?? null)
            ->pluck('id');

        // Si no hay repases, retornar colección vacía de exámenes con valores en cero
        if ($repasesIds->isEmpty()) {
            $query = Examen::query();
            
            if (isset($filtros['examen_id']) && $filtros['examen_id']) {
                $query->where('id', $filtros['examen_id']);
            }
            
            return $query->get()->map(function ($examen) {
                return (object) [
                    'examen_id' => $examen->id,
                    'nombre_examen' => $examen->nombre,
                    'cantidad_total' => 0,
                    'total_ingresos' => 0,
                    'ingreso_promedio' => null,
                ];
            });
        }

        $query = Examen::query()
            ->select([
                'examenes.id as examen_id',
                'examenes.nombre as nombre_examen',
                DB::raw('COALESCE(SUM(repase_examenes.cantidad), 0) as cantidad_total'),
                DB::raw('COALESCE(SUM(repase_examenes.subtotal), 0) as total_ingresos'),
            ])
            ->leftJoin('repase_examenes', function ($join) use ($repasesIds) {
                $join->on('examenes.id', '=', 'repase_examenes.examen_id')
                    ->whereIn('repase_examenes.repase_id', $repasesIds);
            })
            ->groupBy('examenes.id', 'examenes.nombre');

        // Filtrar por examen específico si se proporciona
        if (isset($filtros['examen_id']) && $filtros['examen_id']) {
            $query->where('examenes.id', $filtros['examen_id']);
        }

        $resultados = $query->orderBy('total_ingresos', 'desc')->get();

        // Calcular ingreso promedio para cada registro
        return $resultados->map(function ($registro) {
            $cantidadTotal = (int) $registro->cantidad_total;
            $totalIngresos = (float) $registro->total_ingresos;
            
            $registro->ingreso_promedio = $cantidadTotal > 0 
                ? round($totalIngresos / $cantidadTotal, 2)
                : null;
            
            return $registro;
        });
    }

    /**
     * Calcula métricas de productividad
     * 
     * Genera un reporte con métricas de cantidad de exámenes realizados:
     * - total_examenes_realizados: suma total de cantidades
     * - examenes_por_dia: promedio diario
     * - total_repases: conteo de repases
     * - examenes_por_repase: promedio por repase
     * - por_examen: desglose por tipo de examen
     * - por_clinica: desglose por clínica
     * 
     * @param array $filtros ['fecha_inicio', 'fecha_fin', 'clinica_id']
     * @return array
     */
    public function calcularProductividad(array $filtros): array
    {
        // Construir query base con filtros
        $repasesQuery = Repase::query()
            ->byDateRange($filtros['fecha_inicio'] ?? null, $filtros['fecha_fin'] ?? null)
            ->byClinica($filtros['clinica_id'] ?? null);

        // Calcular total de exámenes realizados
        $totalExamenesRealizados = RepaseExamen::query()
            ->whereIn('repase_id', (clone $repasesQuery)->pluck('id'))
            ->sum('cantidad');

        // Calcular total de repases
        $totalRepases = (clone $repasesQuery)->count();

        // Calcular número de días en el período
        $fechaInicio = $filtros['fecha_inicio'] ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $filtros['fecha_fin'] ?? now()->format('Y-m-d');
        $diasEnPeriodo = max(1, (strtotime($fechaFin) - strtotime($fechaInicio)) / 86400 + 1);

        // Calcular exámenes por día
        $examenesPorDia = $diasEnPeriodo > 0 
            ? round($totalExamenesRealizados / $diasEnPeriodo, 2)
            : 0;

        // Calcular exámenes por repase
        $examenesPorRepase = $totalRepases > 0 
            ? round($totalExamenesRealizados / $totalRepases, 2)
            : 0;

        // Desglose por tipo de examen
        $porExamen = Examen::query()
            ->select([
                'examenes.id as examen_id',
                'examenes.nombre as nombre_examen',
                DB::raw('COALESCE(SUM(repase_examenes.cantidad), 0) as cantidad_total'),
            ])
            ->leftJoin('repase_examenes', 'examenes.id', '=', 'repase_examenes.examen_id')
            ->whereIn('repase_examenes.repase_id', (clone $repasesQuery)->pluck('id'))
            ->groupBy('examenes.id', 'examenes.nombre')
            ->orderBy('cantidad_total', 'desc')
            ->get();

        // Desglose por clínica
        $repasesIds = (clone $repasesQuery)->pluck('id');
        
        $porClinicaQuery = Clinica::query()
            ->select([
                'clinicas.id as clinica_id',
                'clinicas.nombre as nombre_clinica',
                DB::raw('COALESCE(SUM(repase_examenes.cantidad), 0) as cantidad_total'),
            ])
            ->leftJoin('repases', 'clinicas.id', '=', 'repases.clinica_id')
            ->leftJoin('repase_examenes', 'repases.id', '=', 'repase_examenes.repase_id')
            ->whereIn('repases.id', $repasesIds)
            ->when(EmpresaContext::isSet(), fn($q) => $q->where('clinicas.empresa_id', EmpresaContext::get()))
            ->groupBy('clinicas.id', 'clinicas.nombre');

        if (isset($filtros['clinica_id']) && $filtros['clinica_id']) {
            $porClinicaQuery->where('clinicas.id', $filtros['clinica_id']);
        }

        $porClinica = $porClinicaQuery->orderBy('cantidad_total', 'desc')->get();

        return [
            'total_examenes_realizados' => (int) $totalExamenesRealizados,
            'examenes_por_dia' => $examenesPorDia,
            'total_repases' => $totalRepases,
            'examenes_por_repase' => $examenesPorRepase,
            'por_examen' => $porExamen,
            'por_clinica' => $porClinica,
        ];
    }

    /**
     * Calcula comparativo entre dos períodos
     * 
     * Compara métricas financieras entre dos períodos temporales:
     * - total_ingresos, total_gastos, ganancia_neta para cada período
     * - variaciones porcentuales entre períodos
     * 
     * @param array $periodoActual ['fecha_inicio', 'fecha_fin']
     * @param array $periodoAnterior ['fecha_inicio', 'fecha_fin']
     * @param array $filtros ['clinica_id']
     * @return array
     */
    public function calcularComparativo(
        array $periodoActual,
        array $periodoAnterior,
        array $filtros = []
    ): array {
        // Calcular métricas para período actual
        $metricasActual = $this->calcularMetricasPeriodo(
            $periodoActual['fecha_inicio'],
            $periodoActual['fecha_fin'],
            $filtros['clinica_id'] ?? null
        );

        // Calcular métricas para período anterior
        $metricasAnterior = $this->calcularMetricasPeriodo(
            $periodoAnterior['fecha_inicio'],
            $periodoAnterior['fecha_fin'],
            $filtros['clinica_id'] ?? null
        );

        // Calcular variaciones porcentuales
        $variaciones = [
            'ingresos_variacion' => $this->calcularVariacionPorcentual(
                $metricasActual['total_ingresos'],
                $metricasAnterior['total_ingresos']
            ),
            'gastos_variacion' => $this->calcularVariacionPorcentual(
                $metricasActual['total_gastos'],
                $metricasAnterior['total_gastos']
            ),
            'ganancia_variacion' => $this->calcularVariacionPorcentual(
                $metricasActual['ganancia_neta'],
                $metricasAnterior['ganancia_neta']
            ),
        ];

        return [
            'periodo_actual' => array_merge($periodoActual, $metricasActual),
            'periodo_anterior' => array_merge($periodoAnterior, $metricasAnterior),
            'variaciones' => $variaciones,
        ];
    }

    /**
     * Calcula métricas financieras para un período específico
     * 
     * Método auxiliar para calcular ingresos, gastos y ganancia neta
     * en un rango de fechas determinado.
     * 
     * @param string $fechaInicio
     * @param string $fechaFin
     * @param int|null $clinicaId
     * @return array
     */
    protected function calcularMetricasPeriodo(
        string $fechaInicio,
        string $fechaFin,
        ?int $clinicaId = null
    ): array {
        $query = Repase::query()
            ->byDateRange($fechaInicio, $fechaFin)
            ->byClinica($clinicaId);

        $resultado = $query->selectRaw('
            COALESCE(SUM(total_examenes), 0) as total_ingresos,
            COALESCE(SUM(total_gastos), 0) as total_gastos,
            COALESCE(SUM(total_neto), 0) as ganancia_neta
        ')->first();

        return [
            'total_ingresos' => (float) $resultado->total_ingresos,
            'total_gastos' => (float) $resultado->total_gastos,
            'ganancia_neta' => (float) $resultado->ganancia_neta,
        ];
    }

    /**
     * Calcula margen de ganancia
     * 
     * Fórmula: ((ingresos - gastos) / ingresos) * 100
     * Maneja división por cero retornando null cuando ingresos = 0
     * 
     * @param float $ingresos
     * @param float $gastos
     * @return float|null Retorna null si ingresos es 0
     */
    public function calcularMargenGanancia(float $ingresos, float $gastos): ?float
    {
        if ($ingresos == 0) {
            return null;
        }

        return round((($ingresos - $gastos) / $ingresos) * 100, 2);
    }

    /**
     * Calcula variación porcentual entre dos valores
     * 
     * Fórmula: ((valor_actual - valor_anterior) / valor_anterior) * 100
     * Maneja división por cero retornando null cuando valor_anterior = 0
     * 
     * @param float $valorActual
     * @param float $valorAnterior
     * @return float|null Retorna null si valorAnterior es 0
     */
    public function calcularVariacionPorcentual(
        float $valorActual,
        float $valorAnterior
    ): ?float {
        if ($valorAnterior == 0) {
            return null;
        }

        return round((($valorActual - $valorAnterior) / $valorAnterior) * 100, 2);
    }

    /**
     * Calcula análisis de consultas médicas
     * 
     * Genera un reporte con métricas de consultas médicas:
     * - total_consultas: suma total de consultas
     * - consultas_por_dia: promedio diario
     * - consultas_por_repase: promedio por repase
     * - por_clinica: desglose por clínica con ranking
     * - por_mes: evolución mensual de consultas
     * - ratio_consultas_examenes: relación entre consultas y exámenes
     * 
     * @param array $filtros ['fecha_inicio', 'fecha_fin', 'clinica_id']
     * @return array
     */
    public function calcularAnalisisConsultas(array $filtros): array
    {
        // Construir query base con filtros
        $repasesQuery = Repase::query()
            ->byDateRange($filtros['fecha_inicio'] ?? null, $filtros['fecha_fin'] ?? null)
            ->byClinica($filtros['clinica_id'] ?? null);

        // Calcular total de consultas
        $totalConsultas = (clone $repasesQuery)->sum('total_consultas');

        // Calcular total de repases
        $totalRepases = (clone $repasesQuery)->count();

        // Calcular número de días en el período
        $fechaInicio = $filtros['fecha_inicio'] ?? now()->startOfMonth()->format('Y-m-d');
        $fechaFin = $filtros['fecha_fin'] ?? now()->format('Y-m-d');
        $diasEnPeriodo = max(1, (strtotime($fechaFin) - strtotime($fechaInicio)) / 86400 + 1);

        // Calcular consultas por día
        $consultasPorDia = $diasEnPeriodo > 0 
            ? round($totalConsultas / $diasEnPeriodo, 2)
            : 0;

        // Calcular consultas por repase
        $consultasPorRepase = $totalRepases > 0 
            ? round($totalConsultas / $totalRepases, 2)
            : 0;

        // Desglose por clínica
        $repasesIds = (clone $repasesQuery)->pluck('id');
        
        $porClinicaQuery = Clinica::query()
            ->select([
                'clinicas.id as clinica_id',
                'clinicas.nombre as nombre_clinica',
                DB::raw('COALESCE(SUM(repases.total_consultas), 0) as total_consultas'),
                DB::raw('COUNT(repases.id) as cantidad_repases'),
            ])
            ->leftJoin('repases', 'clinicas.id', '=', 'repases.clinica_id')
            ->whereIn('repases.id', $repasesIds)
            ->when(EmpresaContext::isSet(), fn($q) => $q->where('clinicas.empresa_id', EmpresaContext::get()))
            ->groupBy('clinicas.id', 'clinicas.nombre');

        if (isset($filtros['clinica_id']) && $filtros['clinica_id']) {
            $porClinicaQuery->where('clinicas.id', $filtros['clinica_id']);
        }

        $porClinica = $porClinicaQuery->orderBy('total_consultas', 'desc')->get();

        // Calcular promedio de consultas por repase para cada clínica
        $porClinica = $porClinica->map(function ($clinica) {
            $clinica->consultas_por_repase = $clinica->cantidad_repases > 0
                ? round($clinica->total_consultas / $clinica->cantidad_repases, 2)
                : 0;
            return $clinica;
        });

        // Evolución mensual de consultas
        $driver = DB::connection()->getDriverName();
        $dateFormat = $driver === 'sqlite' 
            ? "strftime('%Y-%m', fecha)" 
            : "DATE_FORMAT(fecha, '%Y-%m')";

        $porMes = Repase::query()
            ->byDateRange($filtros['fecha_inicio'] ?? null, $filtros['fecha_fin'] ?? null)
            ->byClinica($filtros['clinica_id'] ?? null)
            ->selectRaw("
                {$dateFormat} as mes,
                SUM(total_consultas) as total_consultas,
                COUNT(id) as cantidad_repases
            ")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();

        // Calcular total de exámenes para ratio
        $totalExamenes = RepaseExamen::query()
            ->whereIn('repase_id', $repasesIds)
            ->sum('cantidad');

        // Calcular ratio exámenes/consultas
        $ratioExamenesConsultas = $totalConsultas > 0
            ? round($totalExamenes / $totalConsultas, 2)
            : null;

        return [
            'total_consultas' => (int) $totalConsultas,
            'consultas_por_dia' => $consultasPorDia,
            'total_repases' => $totalRepases,
            'consultas_por_repase' => $consultasPorRepase,
            'total_examenes' => (int) $totalExamenes,
            'ratio_examenes_consultas' => $ratioExamenesConsultas,
            'por_clinica' => $porClinica,
            'por_mes' => $porMes,
        ];
    }

    /**
     * Compara rentabilidad entre dos clínicas en el mismo período
     * 
     * Genera un reporte comparativo lado a lado de dos clínicas:
     * - Métricas financieras de cada clínica
     * - Diferencias absolutas y porcentuales
     * - Análisis de rendimiento relativo
     * 
     * @param int $clinicaId1 ID de la primera clínica
     * @param int $clinicaId2 ID de la segunda clínica
     * @param string $fechaInicio Fecha de inicio del período
     * @param string $fechaFin Fecha de fin del período
     * @return array
     */
    public function compararClinicas(
        int $clinicaId1,
        int $clinicaId2,
        string $fechaInicio,
        string $fechaFin
    ): array {
        // Obtener datos de la primera clínica
        $clinica1 = $this->calcularRentabilidadClinica([
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'clinica_id' => $clinicaId1,
        ])->first();

        // Obtener datos de la segunda clínica
        $clinica2 = $this->calcularRentabilidadClinica([
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'clinica_id' => $clinicaId2,
        ])->first();

        // Si alguna clínica no tiene datos, retornar estructura vacía
        if (!$clinica1 || !$clinica2) {
            return [
                'clinica_1' => null,
                'clinica_2' => null,
                'diferencias' => null,
                'ganador' => null,
            ];
        }

        // Calcular diferencias
        $diferencias = [
            'total_ingresos' => [
                'absoluta' => $clinica1->total_ingresos - $clinica2->total_ingresos,
                'porcentual' => $this->calcularVariacionPorcentual(
                    $clinica1->total_ingresos,
                    $clinica2->total_ingresos
                ),
            ],
            'total_gastos' => [
                'absoluta' => $clinica1->total_gastos - $clinica2->total_gastos,
                'porcentual' => $this->calcularVariacionPorcentual(
                    $clinica1->total_gastos,
                    $clinica2->total_gastos
                ),
            ],
            'ganancia_neta' => [
                'absoluta' => $clinica1->ganancia_neta - $clinica2->ganancia_neta,
                'porcentual' => $this->calcularVariacionPorcentual(
                    $clinica1->ganancia_neta,
                    $clinica2->ganancia_neta
                ),
            ],
            'cantidad_repases' => [
                'absoluta' => $clinica1->cantidad_repases - $clinica2->cantidad_repases,
                'porcentual' => $this->calcularVariacionPorcentual(
                    $clinica1->cantidad_repases,
                    $clinica2->cantidad_repases
                ),
            ],
        ];

        // Determinar ganador basado en ganancia neta
        $ganador = null;
        if ($clinica1->ganancia_neta > $clinica2->ganancia_neta) {
            $ganador = 'clinica_1';
        } elseif ($clinica2->ganancia_neta > $clinica1->ganancia_neta) {
            $ganador = 'clinica_2';
        } else {
            $ganador = 'empate';
        }

        return [
            'clinica_1' => $clinica1,
            'clinica_2' => $clinica2,
            'diferencias' => $diferencias,
            'ganador' => $ganador,
            'periodo' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
            ],
        ];
    }

    public function getRepasesConGastos(array $filtros, bool $paginated = true): LengthAwarePaginator|Collection
    {
        $query = Repase::with(['clinica:id,nombre', 'gastos'])
            ->byDateRange($filtros['fecha_inicio'] ?? null, $filtros['fecha_fin'] ?? null)
            ->byClinica($filtros['clinica_id'] ?? null)
            ->orderBy('fecha', 'desc')
            ->orderBy('id', 'desc');

        if ($paginated) {
            return $query->paginate(25)->withQueryString();
        }

        return $query->get();
    }
}
