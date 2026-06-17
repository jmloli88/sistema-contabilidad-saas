<?php

namespace App\Services;

use App\Models\Repase;
use App\Models\Clinica;
use App\Models\Examen;
use App\Models\RepaseExamen;
use App\Support\EmpresaContext;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    public function getBalancesPorPeriodo(string $periodo, array $filtros = []): Collection
    {
        $driver = DB::connection()->getDriverName();
        $dateFormat = $this->getDateFormat($periodo, $driver);

        $query = Repase::query()
            ->selectRaw("
                {$dateFormat} as period,
                COUNT(*) as total_repases,
                COALESCE(SUM(total_examenes), 0) as total_ingresos,
                COALESCE(SUM(total_gastos), 0) as total_gastos,
                COALESCE(SUM(total_neto), 0) as total_neto,
                COALESCE(SUM(total_consultas), 0) as total_consultas,
                COALESCE(SUM(pedidos_doctor), 0) as total_pedidos_doctor
            ")
            ->when($filtros['clinica_id'] ?? null, fn($q, $id) => $q->where('clinica_id', $id))
            ->when($filtros['estado'] ?? null, fn($q, $e) => $q->where('estado', $e))
            ->when($filtros['fecha_inicio'] ?? null, fn($q, $f) => $q->where('fecha', '>=', $f))
            ->when($filtros['fecha_fin'] ?? null, fn($q, $f) => $q->where('fecha', '<=', $f))
            ->groupBy('period')
            ->orderBy('period');

        $resultados = $query->get();

        return $resultados->map(function ($item) use ($periodo) {
            $item->period_label = $this->getPeriodLabel($item->period, $periodo);
            $item->margen_ganancia = $this->calcularMargen((float) $item->total_ingresos, (float) $item->total_gastos);
            return $item;
        });
    }

    public function getResumenEjecutivo(array $filtros = []): array
    {
        $query = Repase::query()
            ->selectRaw("
                COALESCE(SUM(total_examenes), 0) as total_ingresos,
                COALESCE(SUM(total_gastos), 0) as total_gastos,
                COALESCE(SUM(total_neto), 0) as total_neto,
                COUNT(*) as total_repases,
                COALESCE(SUM(total_consultas), 0) as total_consultas,
                COALESCE(SUM(pedidos_doctor), 0) as total_pedidos_doctor
            ")
            ->when($filtros['clinica_id'] ?? null, fn($q, $id) => $q->where('clinica_id', $id))
            ->when($filtros['estado'] ?? null, fn($q, $e) => $q->where('estado', $e))
            ->when($filtros['fecha_inicio'] ?? null, fn($q, $f) => $q->where('fecha', '>=', $f))
            ->when($filtros['fecha_fin'] ?? null, fn($q, $f) => $q->where('fecha', '<=', $f));

        $resumen = $query->first();

        $repasesPagados = Repase::query()
            ->where('estado', 'pagado')
            ->when($filtros['clinica_id'] ?? null, fn($q, $id) => $q->where('clinica_id', $id))
            ->when($filtros['fecha_inicio'] ?? null, fn($q, $f) => $q->where('fecha', '>=', $f))
            ->when($filtros['fecha_fin'] ?? null, fn($q, $f) => $q->where('fecha', '<=', $f))
            ->count();

        $ingresos = (float) $resumen->total_ingresos;
        $gastos = (float) $resumen->total_gastos;

        return [
            'total_ingresos' => $ingresos,
            'total_gastos' => $gastos,
            'total_neto' => (float) $resumen->total_neto,
            'total_repases' => (int) $resumen->total_repases,
            'total_consultas' => (int) $resumen->total_consultas,
            'total_pedidos_doctor' => (int) $resumen->total_pedidos_doctor,
            'repases_pagados' => $repasesPagados,
            'repases_pendientes' => (int) $resumen->total_repases - $repasesPagados,
            'margen_ganancia' => $this->calcularMargen($ingresos, $gastos),
        ];
    }

    public function getRepasesDelPeriodo(int $year, string $periodo, int $periodIndex, array $filtros = []): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        [$fechaInicio, $fechaFin] = $this->getPeriodBoundaries($year, $periodo, $periodIndex);

        return Repase::with(['clinica:id,nombre'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->when($filtros['clinica_id'] ?? null, fn($q, $id) => $q->where('clinica_id', $id))
            ->when($filtros['estado'] ?? null, fn($q, $e) => $q->where('estado', $e))
            ->orderBy('fecha')
            ->orderBy('id')
            ->paginate(20);
    }

    public function getGastosPorCategoria(string $periodo, array $filtros = []): Collection
    {
        $driver = DB::connection()->getDriverName();
        $dateFormat = $this->getDateFormat($periodo, $driver);

        return DB::table('gastos')
            ->join('repases', 'gastos.repase_id', '=', 'repases.id')
            ->join('clinicas', 'repases.clinica_id', '=', 'clinicas.id')
            ->selectRaw("
                {$dateFormat} as period,
                gastos.tipo,
                COALESCE(SUM(gastos.monto), 0) as total
            ")
            ->whereNull('repases.deleted_at')
            ->when(EmpresaContext::isSet(), fn($q) => $q->where('clinicas.empresa_id', EmpresaContext::get()))
            ->when($filtros['clinica_id'] ?? null, fn($q, $id) => $q->where('repases.clinica_id', $id))
            ->when($filtros['fecha_inicio'] ?? null, fn($q, $f) => $q->where('repases.fecha', '>=', $f))
            ->when($filtros['fecha_fin'] ?? null, fn($q, $f) => $q->where('repases.fecha', '<=', $f))
            ->groupBy('period', 'gastos.tipo')
            ->orderBy('period')
            ->get();
    }

    public function getTopExamenes(string $periodo, array $filtros = [], int $limit = 10): Collection
    {
        $driver = DB::connection()->getDriverName();
        $dateFormat = $this->getDateFormat($periodo, $driver);

        return DB::table('repase_examenes')
            ->join('repases', 'repase_examenes.repase_id', '=', 'repases.id')
            ->join('examenes', 'repase_examenes.examen_id', '=', 'examenes.id')
            ->join('clinicas', 'repases.clinica_id', '=', 'clinicas.id')
            ->selectRaw("
                {$dateFormat} as period,
                examenes.id as examen_id,
                examenes.nombre as nombre_examen,
                COALESCE(SUM(repase_examenes.cantidad), 0) as cantidad_total,
                COALESCE(SUM(repase_examenes.subtotal), 0) as total_ingresos
            ")
            ->whereNull('repases.deleted_at')
            ->when(EmpresaContext::isSet(), fn($q) => $q->where('clinicas.empresa_id', EmpresaContext::get()))
            ->when($filtros['clinica_id'] ?? null, fn($q, $id) => $q->where('repases.clinica_id', $id))
            ->when($filtros['fecha_inicio'] ?? null, fn($q, $f) => $q->where('repases.fecha', '>=', $f))
            ->when($filtros['fecha_fin'] ?? null, fn($q, $f) => $q->where('repases.fecha', '<=', $f))
            ->groupBy('period', 'examenes.id', 'examenes.nombre')
            ->orderBy('total_ingresos', 'desc')
            ->limit($limit)
            ->get();
    }

    public function compararPeriodos(string $periodo, int $year1, int $periodIndex1, int $year2, int $periodIndex2, array $filtros = []): array
    {
        [$fechaInicio1, $fechaFin1] = $this->getPeriodBoundaries($year1, $periodo, $periodIndex1);
        [$fechaInicio2, $fechaFin2] = $this->getPeriodBoundaries($year2, $periodo, $periodIndex2);

        $periodo1 = $this->getResumenEjecutivo(array_merge($filtros, [
            'fecha_inicio' => $fechaInicio1,
            'fecha_fin' => $fechaFin1,
        ]));

        $periodo2 = $this->getResumenEjecutivo(array_merge($filtros, [
            'fecha_inicio' => $fechaInicio2,
            'fecha_fin' => $fechaFin2,
        ]));

        return [
            'periodo_1' => array_merge([
                'label' => $this->getPeriodLabel($year1 . '-' . $periodIndex1, $periodo),
                'fecha_inicio' => $fechaInicio1,
                'fecha_fin' => $fechaFin1,
            ], $periodo1),
            'periodo_2' => array_merge([
                'label' => $this->getPeriodLabel($year2 . '-' . $periodIndex2, $periodo),
                'fecha_inicio' => $fechaInicio2,
                'fecha_fin' => $fechaFin2,
            ], $periodo2),
            'variaciones' => [
                'ingresos' => $this->calcularVariacion($periodo1['total_ingresos'], $periodo2['total_ingresos']),
                'gastos' => $this->calcularVariacion($periodo1['total_gastos'], $periodo2['total_gastos']),
                'neto' => $this->calcularVariacion($periodo1['total_neto'], $periodo2['total_neto']),
                'repases' => $this->calcularVariacion($periodo1['total_repases'], $periodo2['total_repases']),
            ],
        ];
    }

    private function getDateFormat(string $periodo, string $driver): string
    {
        $formats = [
            'month' => [
                'mysql' => "DATE_FORMAT(fecha, '%Y-%m')",
                'sqlite' => "strftime('%Y-%m', fecha)",
            ],
            'quarter' => [
                'mysql' => "CONCAT(YEAR(fecha), '-Q', QUARTER(fecha))",
                'sqlite' => "strftime('%Y', fecha) || '-Q' || ((strftime('%m', fecha) - 1) / 3 + 1)",
            ],
            'semester' => [
                'mysql' => "CONCAT(YEAR(fecha), '-S', CASE WHEN MONTH(fecha) <= 6 THEN '1' ELSE '2' END)",
                'sqlite' => "strftime('%Y', fecha) || '-S' || CASE WHEN CAST(strftime('%m', fecha) AS INTEGER) <= 6 THEN '1' ELSE '2' END",
            ],
            'year' => [
                'mysql' => "YEAR(fecha)",
                'sqlite' => "strftime('%Y', fecha)",
            ],
        ];

        return $formats[$periodo][$driver] ?? $formats[$periodo]['sqlite'];
    }

    private function getPeriodLabel(string $period, string $periodo): string
    {
        $months = [
            '01' => 'Enero', '02' => 'Febrero', '03' => 'Marzo',
            '04' => 'Abril', '05' => 'Mayo', '06' => 'Junio',
            '07' => 'Julio', '08' => 'Agosto', '09' => 'Septiembre',
            '10' => 'Octubre', '11' => 'Noviembre', '12' => 'Diciembre',
        ];

        return match ($periodo) {
            'month' => $this->getMonthLabel($period, $months),
            'quarter' => str_replace('-Q', ' Trimestre ', $period),
            'semester' => str_replace('-S1', ' Semestre 1', str_replace('-S2', ' Semestre 2', $period)),
            'year' => $period,
            default => $period,
        };
    }

    private function getMonthLabel(string $period, array $months): string
    {
        $parts = explode('-', $period);
        $monthNum = $parts[1] ?? '01';
        $monthName = $months[$monthNum] ?? $monthNum;
        return "{$monthName} {$parts[0]}";
    }

    private function getPeriodBoundaries(int $year, string $periodo, int $periodIndex): array
    {
        return match ($periodo) {
            'month' => [
                "{$year}-" . str_pad($periodIndex, 2, '0', STR_PAD_LEFT) . "-01",
                date('Y-m-t', strtotime("{$year}-" . str_pad($periodIndex, 2, '0', STR_PAD_LEFT) . "-01")),
            ],
            'quarter' => [
                "{$year}-" . str_pad(($periodIndex - 1) * 3 + 1, 2, '0', STR_PAD_LEFT) . "-01",
                date('Y-m-t', strtotime("{$year}-" . str_pad($periodIndex * 3, 2, '0', STR_PAD_LEFT) . "-01")),
            ],
            'semester' => [
                $periodIndex === 1 ? "{$year}-01-01" : "{$year}-07-01",
                $periodIndex === 1 ? "{$year}-06-30" : "{$year}-12-31",
            ],
            'year' => [
                "{$year}-01-01",
                "{$year}-12-31",
            ],
            default => ["{$year}-01-01", "{$year}-12-31"],
        };
    }

    private function calcularMargen(float $ingresos, float $gastos): ?float
    {
        if ($ingresos == 0) {
            return null;
        }
        return round((($ingresos - $gastos) / $ingresos) * 100, 2);
    }

    private function calcularVariacion(float $actual, float $anterior): ?float
    {
        if ($anterior == 0) {
            return null;
        }
        return round((($actual - $anterior) / $anterior) * 100, 2);
    }
}
