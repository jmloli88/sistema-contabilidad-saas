<?php

namespace App\Services;

use App\Models\Repase;
use Illuminate\Support\Facades\DB;

/**
 * Servicio para gestionar la lógica de negocio del Dashboard
 * 
 * Este servicio maneja el cálculo de métricas financieras y la preparación
 * de datos para los gráficos del dashboard, aplicando filtros según los
 * criterios proporcionados por el usuario.
 */
class DashboardService
{
    /**
     * Calcula las métricas financieras del dashboard según los filtros aplicados
     * 
     * Este método calcula:
     * - total_ingresos: Suma de total_examenes de todos los repases
     * - total_gastos: Suma de total_gastos de todos los repases
     * - total_neto: total_ingresos - total_gastos
     * - total_pendiente: Suma de total_neto de repases con estado "pendiente"
     * - total_pagado: Suma de total_neto de repases con estado "pagado"
     * 
     * Los filtros se aplican usando los scopes del modelo Repase:
     * - clinica_id: Filtra por clínica específica
     * - estado: Filtra por estado (pendiente/pagado)
     * - fecha_desde: Fecha de inicio del rango
     * - fecha_hasta: Fecha de fin del rango
     * 
     * @param array $filters Array de filtros con claves: clinica_id, estado, fecha_desde, fecha_hasta
     * @return array Array con las métricas calculadas
     */
    public function getMetrics(array $filters): array
    {
        // Construir query base con filtros aplicados
        $query = Repase::query()
            ->byClinica($filters['clinica_id'] ?? null)
            ->byEstado($filters['estado'] ?? null)
            ->byDateRange($filters['fecha_desde'] ?? null, $filters['fecha_hasta'] ?? null);
        
        // Calcular total_ingresos: suma de total_examenes
        $totalIngresos = $query->sum('total_examenes');
        
        // Calcular total_gastos: suma de total_gastos
        $totalGastos = $query->sum('total_gastos');
        
        // Calcular total_neto: ingresos - gastos
        $totalNeto = $totalIngresos - $totalGastos;
        
        // Calcular total_pendiente: suma de total_neto donde estado = 'pendiente'
        $totalPendiente = Repase::query()
            ->byClinica($filters['clinica_id'] ?? null)
            ->byDateRange($filters['fecha_desde'] ?? null, $filters['fecha_hasta'] ?? null)
            ->where('estado', 'pendiente')
            ->sum('total_neto');
        
        // Calcular total_pagado: suma de total_neto donde estado = 'pagado'
        $totalPagado = Repase::query()
            ->byClinica($filters['clinica_id'] ?? null)
            ->byDateRange($filters['fecha_desde'] ?? null, $filters['fecha_hasta'] ?? null)
            ->where('estado', 'pagado')
            ->sum('total_neto');
        
        return [
            'total_ingresos' => round($totalIngresos, 2),
            'total_gastos' => round($totalGastos, 2),
            'total_neto' => round($totalNeto, 2),
            'total_pendiente' => round($totalPendiente, 2),
            'total_pagado' => round($totalPagado, 2),
        ];
    }
    
    /**
     * Obtiene datos para el gráfico de Ingresos vs Gastos por mes
     * 
     * Este método agrupa los repases por mes y calcula los ingresos totales
     * y gastos totales para cada mes. Los datos se retornan en formato
     * compatible con Chart.js.
     * 
     * @param array $filters Array de filtros con claves: clinica_id, estado, fecha_desde, fecha_hasta
     * @return array Array con labels (meses) y datasets (ingresos y gastos)
     */
    public function getIngresosVsGastosChart(array $filters): array
    {
        // Determinar la función de formato de fecha según el driver de base de datos
        $driver = DB::connection()->getDriverName();
        $dateFormat = $driver === 'sqlite' 
            ? "strftime('%Y-%m', fecha)" 
            : "DATE_FORMAT(fecha, '%Y-%m')";
        
        // Construir query con filtros aplicados
        $repases = Repase::query()
            ->byClinica($filters['clinica_id'] ?? null)
            ->byEstado($filters['estado'] ?? null)
            ->byDateRange($filters['fecha_desde'] ?? null, $filters['fecha_hasta'] ?? null)
            ->selectRaw("
                {$dateFormat} as mes,
                SUM(total_examenes) as ingresos,
                SUM(total_gastos) as gastos
            ")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();
        
        // Preparar datos para Chart.js
        $labels = [];
        $ingresos = [];
        $gastos = [];
        
        foreach ($repases as $repase) {
            $labels[] = $repase->mes;
            $ingresos[] = round($repase->ingresos, 2);
            $gastos[] = round($repase->gastos, 2);
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Ingresos',
                    'data' => $ingresos,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.5)',
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'borderWidth' => 2,
                ],
                [
                    'label' => 'Gastos',
                    'data' => $gastos,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.5)',
                    'borderColor' => 'rgba(239, 68, 68, 1)',
                    'borderWidth' => 2,
                ],
            ],
        ];
    }
    
    /**
     * Obtiene datos para el gráfico de Totales por Clínica
     * 
     * Este método agrupa los repases por clínica y calcula el total neto
     * para cada una. Los datos se retornan en formato compatible con Chart.js
     * para un gráfico de pastel.
     * 
     * @param array $filters Array de filtros con claves: estado, fecha_desde, fecha_hasta
     * @return array Array con labels (nombres de clínicas) y data (totales netos)
     */
    public function getTotalesPorClinicaChart(array $filters): array
    {
        // Construir query con filtros aplicados (sin filtro de clínica para mostrar todas)
        $repases = Repase::query()
            ->with('clinica')
            ->byEstado($filters['estado'] ?? null)
            ->byDateRange($filters['fecha_desde'] ?? null, $filters['fecha_hasta'] ?? null)
            ->selectRaw('
                clinica_id,
                SUM(total_neto) as total
            ')
            ->groupBy('clinica_id')
            ->orderByDesc('total')
            ->get();
        
        // Preparar datos para Chart.js
        $labels = [];
        $data = [];
        $backgroundColors = [
            'rgba(59, 130, 246, 0.7)',
            'rgba(34, 197, 94, 0.7)',
            'rgba(251, 146, 60, 0.7)',
            'rgba(168, 85, 247, 0.7)',
            'rgba(236, 72, 153, 0.7)',
            'rgba(14, 165, 233, 0.7)',
            'rgba(132, 204, 22, 0.7)',
        ];
        
        foreach ($repases as $index => $repase) {
            $labels[] = $repase->clinica->nombre ?? 'Sin clínica';
            $data[] = round($repase->total, 2);
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Neto por Clínica',
                    'data' => $data,
                    'backgroundColor' => array_slice($backgroundColors, 0, count($data)),
                    'borderWidth' => 1,
                ],
            ],
        ];
    }
    
    /**
     * Obtiene datos para el gráfico de Pagados vs Pendientes
     * 
     * Este método calcula el total neto de repases pagados y pendientes.
     * Los datos se retornan en formato compatible con Chart.js para un
     * gráfico de dona.
     * 
     * @param array $filters Array de filtros con claves: clinica_id, fecha_desde, fecha_hasta
     * @return array Array con labels (estados) y data (totales netos)
     */
    public function getPagadosVsPendientesChart(array $filters): array
    {
        // Construir query con filtros aplicados (sin filtro de estado para mostrar ambos)
        $repases = Repase::query()
            ->byClinica($filters['clinica_id'] ?? null)
            ->byDateRange($filters['fecha_desde'] ?? null, $filters['fecha_hasta'] ?? null)
            ->selectRaw('
                estado,
                SUM(total_neto) as total
            ')
            ->groupBy('estado')
            ->get();
        
        // Preparar datos para Chart.js
        $labels = [];
        $data = [];
        $backgroundColors = [];
        
        foreach ($repases as $repase) {
            $labels[] = ucfirst($repase->estado);
            $data[] = round($repase->total, 2);
            
            // Asignar colores según el estado
            $backgroundColors[] = $repase->estado === 'pagado'
                ? 'rgba(34, 197, 94, 0.7)'
                : 'rgba(239, 68, 68, 0.7)';
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total Neto',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderWidth' => 1,
                ],
            ],
        ];
    }
    
    /**
     * Obtiene datos para el gráfico de Desglose de Gastos por Categoría
     * 
     * @param array $filters Array de filtros
     * @return array Array con labels y data para Chart.js
     */
    public function getGastosPorCategoriaChart(array $filters): array
    {
        $gastos = DB::table('gastos')
            ->join('repases', 'gastos.repase_id', '=', 'repases.id')
            ->when($filters['clinica_id'] ?? null, function ($query, $clinicaId) {
                return $query->where('repases.clinica_id', $clinicaId);
            })
            ->when($filters['estado'] ?? null, function ($query, $estado) {
                return $query->where('repases.estado', $estado);
            })
            ->when($filters['fecha_desde'] ?? null, function ($query, $fechaDesde) {
                return $query->where('repases.fecha', '>=', $fechaDesde);
            })
            ->when($filters['fecha_hasta'] ?? null, function ($query, $fechaHasta) {
                return $query->where('repases.fecha', '<=', $fechaHasta);
            })
            ->selectRaw('gastos.tipo, SUM(gastos.monto) as total')
            ->groupBy('gastos.tipo')
            ->orderByDesc('total')
            ->get();
        
        $labels = [];
        $data = [];
        $backgroundColors = [
            'rgba(239, 68, 68, 0.7)',    // rojo - doctor
            'rgba(251, 146, 60, 0.7)',   // naranja - tecnico
            'rgba(59, 130, 246, 0.7)',   // azul - laudos
            'rgba(168, 85, 247, 0.7)',   // morado - gasolina
            'rgba(236, 72, 153, 0.7)',   // rosa - extra
        ];
        
        $tipoLabels = [
            'doctor' => 'Honorarios Médicos',
            'tecnico' => 'Técnicos',
            'laudos' => 'Laudos',
            'gasolina' => 'Gasolina',
            'extra' => 'Otros Gastos',
        ];
        
        foreach ($gastos as $index => $gasto) {
            $labels[] = $tipoLabels[$gasto->tipo] ?? ucfirst($gasto->tipo);
            $data[] = round($gasto->total, 2);
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Gastos por Categoría',
                    'data' => $data,
                    'backgroundColor' => array_slice($backgroundColors, 0, count($data)),
                    'borderWidth' => 2,
                    'borderColor' => '#ffffff',
                ],
            ],
        ];
    }
    
    /**
     * Obtiene datos para el gráfico de Top 5 Exámenes Más Rentables
     * 
     * @param array $filters Array de filtros
     * @return array Array con labels y data para Chart.js
     */
    public function getTopExamenesChart(array $filters): array
    {
        $examenes = DB::table('repase_examenes')
            ->join('repases', 'repase_examenes.repase_id', '=', 'repases.id')
            ->join('examenes', 'repase_examenes.examen_id', '=', 'examenes.id')
            ->when($filters['clinica_id'] ?? null, function ($query, $clinicaId) {
                return $query->where('repases.clinica_id', $clinicaId);
            })
            ->when($filters['estado'] ?? null, function ($query, $estado) {
                return $query->where('repases.estado', $estado);
            })
            ->when($filters['fecha_desde'] ?? null, function ($query, $fechaDesde) {
                return $query->where('repases.fecha', '>=', $fechaDesde);
            })
            ->when($filters['fecha_hasta'] ?? null, function ($query, $fechaHasta) {
                return $query->where('repases.fecha', '<=', $fechaHasta);
            })
            ->selectRaw('
                examenes.nombre,
                SUM(repase_examenes.subtotal) as total_ingresos,
                SUM(repase_examenes.cantidad) as cantidad_total
            ')
            ->groupBy('examenes.id', 'examenes.nombre')
            ->orderByDesc('total_ingresos')
            ->limit(5)
            ->get();
        
        $labels = [];
        $data = [];
        
        foreach ($examenes as $examen) {
            $labels[] = $examen->nombre;
            $data[] = round($examen->total_ingresos, 2);
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Ingresos (R$)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.7)',
                    'borderColor' => 'rgba(34, 197, 94, 1)',
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                ],
            ],
        ];
    }
    
    /**
     * Obtiene datos para el gráfico de Evolución de Ingresos Netos
     * 
     * @param array $filters Array de filtros
     * @return array Array con labels y data para Chart.js
     */
    public function getEvolucionIngresosNetosChart(array $filters): array
    {
        $driver = DB::connection()->getDriverName();
        $dateFormat = $driver === 'sqlite' 
            ? "strftime('%Y-%m', fecha)" 
            : "DATE_FORMAT(fecha, '%Y-%m')";
        
        $repases = Repase::query()
            ->byClinica($filters['clinica_id'] ?? null)
            ->byEstado($filters['estado'] ?? null)
            ->byDateRange($filters['fecha_desde'] ?? null, $filters['fecha_hasta'] ?? null)
            ->selectRaw("
                {$dateFormat} as mes,
                SUM(total_neto) as total_neto
            ")
            ->groupBy('mes')
            ->orderBy('mes')
            ->get();
        
        $labels = [];
        $data = [];
        
        foreach ($repases as $repase) {
            $labels[] = $repase->mes;
            $data[] = round($repase->total_neto, 2);
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Ingresos Netos (R$)',
                    'data' => $data,
                    'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                    'borderColor' => 'rgba(59, 130, 246, 1)',
                    'borderWidth' => 3,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
        ];
    }
    
    /**
     * Obtiene datos para el gráfico de Días Promedio de Cobro por Clínica
     * 
     * @param array $filters Array de filtros
     * @return array Array con labels y data para Chart.js
     */
    public function getDiasCobroPorClinicaChart(array $filters): array
    {
        $driver = DB::connection()->getDriverName();
        
        // Calcular diferencia de días según el driver
        if ($driver === 'sqlite') {
            $diasDiff = "CAST((julianday(fecha_pago) - julianday(fecha)) AS INTEGER)";
        } else {
            $diasDiff = "DATEDIFF(fecha_pago, fecha)";
        }
        
        $clinicas = DB::table('repases')
            ->join('clinicas', 'repases.clinica_id', '=', 'clinicas.id')
            ->whereNotNull('repases.fecha_pago')
            ->where('repases.estado', 'pagado')
            ->when($filters['clinica_id'] ?? null, function ($query, $clinicaId) {
                return $query->where('repases.clinica_id', $clinicaId);
            })
            ->when($filters['fecha_desde'] ?? null, function ($query, $fechaDesde) {
                return $query->where('repases.fecha', '>=', $fechaDesde);
            })
            ->when($filters['fecha_hasta'] ?? null, function ($query, $fechaHasta) {
                return $query->where('repases.fecha', '<=', $fechaHasta);
            })
            ->selectRaw("
                clinicas.nombre,
                AVG({$diasDiff}) as dias_promedio
            ")
            ->groupBy('clinicas.id', 'clinicas.nombre')
            ->orderBy('dias_promedio')
            ->get();
        
        $labels = [];
        $data = [];
        $backgroundColors = [];
        
        foreach ($clinicas as $clinica) {
            $labels[] = $clinica->nombre;
            $diasPromedio = round($clinica->dias_promedio, 1);
            $data[] = $diasPromedio;
            
            // Color según días: verde (<=15), amarillo (16-30), rojo (>30)
            if ($diasPromedio <= 15) {
                $backgroundColors[] = 'rgba(34, 197, 94, 0.7)';
            } elseif ($diasPromedio <= 30) {
                $backgroundColors[] = 'rgba(251, 146, 60, 0.7)';
            } else {
                $backgroundColors[] = 'rgba(239, 68, 68, 0.7)';
            }
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Días Promedio',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $backgroundColors,
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                ],
            ],
        ];
    }
    
    /**
     * Obtiene datos para el gráfico de Margen de Ganancia por Clínica
     * 
     * @param array $filters Array de filtros
     * @return array Array con labels y data para Chart.js
     */
    public function getMargenGananciaPorClinicaChart(array $filters): array
    {
        $clinicas = Repase::query()
            ->with('clinica')
            ->byEstado($filters['estado'] ?? null)
            ->byDateRange($filters['fecha_desde'] ?? null, $filters['fecha_hasta'] ?? null)
            ->when($filters['clinica_id'] ?? null, function ($query, $clinicaId) {
                return $query->where('clinica_id', $clinicaId);
            })
            ->selectRaw('
                clinica_id,
                SUM(total_examenes) as total_ingresos,
                SUM(total_gastos) as total_gastos,
                SUM(total_neto) as total_neto
            ')
            ->groupBy('clinica_id')
            ->get();
        
        $labels = [];
        $data = [];
        $backgroundColors = [];
        
        foreach ($clinicas as $clinica) {
            $labels[] = $clinica->clinica->nombre ?? 'Sin clínica';
            
            // Calcular margen: (total_neto / total_ingresos) * 100
            $margen = $clinica->total_ingresos > 0 
                ? ($clinica->total_neto / $clinica->total_ingresos) * 100 
                : 0;
            
            $data[] = round($margen, 2);
            
            // Color según margen: verde (>30%), amarillo (15-30%), rojo (<15%)
            if ($margen > 30) {
                $backgroundColors[] = 'rgba(34, 197, 94, 0.7)';
            } elseif ($margen >= 15) {
                $backgroundColors[] = 'rgba(251, 146, 60, 0.7)';
            } else {
                $backgroundColors[] = 'rgba(239, 68, 68, 0.7)';
            }
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Margen de Ganancia (%)',
                    'data' => $data,
                    'backgroundColor' => $backgroundColors,
                    'borderColor' => $backgroundColors,
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                ],
            ],
        ];
    }
    
    /**
     * Obtiene datos para el gráfico de Top 5 Clínicas con Mayor Cantidad de Consultas
     * 
     * @param array $filters Array de filtros
     * @return array Array con labels y data para Chart.js
     */
    public function getTopClinicasConsultasChart(array $filters): array
    {
        $clinicas = Repase::query()
            ->with('clinica')
            ->byEstado($filters['estado'] ?? null)
            ->byDateRange($filters['fecha_desde'] ?? null, $filters['fecha_hasta'] ?? null)
            ->selectRaw('
                clinica_id,
                SUM(total_consultas) as total_consultas
            ')
            ->groupBy('clinica_id')
            ->orderByDesc('total_consultas')
            ->limit(5)
            ->get();
        
        $labels = [];
        $data = [];
        
        foreach ($clinicas as $clinica) {
            $labels[] = $clinica->clinica->nombre ?? 'Sin clínica';
            $data[] = (int) $clinica->total_consultas;
        }
        
        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Cantidad de Consultas',
                    'data' => $data,
                    'backgroundColor' => 'rgba(99, 102, 241, 0.7)',
                    'borderColor' => 'rgba(99, 102, 241, 1)',
                    'borderWidth' => 2,
                    'borderRadius' => 8,
                ],
            ],
        ];
    }
}
