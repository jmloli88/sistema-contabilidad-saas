<?php

namespace App\Http\Controllers;

use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BalanceController extends Controller
{
    protected BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function index(): View
    {
        $clinicas = \App\Models\Clinica::orderBy('nombre')->get();
        $filtros = [];

        $resumen = $this->balanceService->getResumenEjecutivo($filtros);

        $balancesMensuales = $this->balanceService->getBalancesPorPeriodo('month', [
            'fecha_inicio' => now()->subYear()->startOfMonth()->format('Y-m-d'),
            'fecha_fin' => now()->format('Y-m-d'),
        ]);

        return view('balances.index', compact('clinicas', 'resumen', 'balancesMensuales'));
    }

    public function mensual(Request $request): View
    {
        return $this->renderBalanceView($request, 'mensual', 'month');
    }

    public function trimestral(Request $request): View
    {
        return $this->renderBalanceView($request, 'trimestral', 'quarter');
    }

    public function semestral(Request $request): View
    {
        return $this->renderBalanceView($request, 'semestral', 'semester');
    }

    public function anual(Request $request): View
    {
        return $this->renderBalanceView($request, 'anual', 'year');
    }

    public function detallePeriodo(Request $request): View
    {
        $validated = $request->validate([
            'periodo' => 'required|in:mensual,trimestral,semestral,anual',
            'anio' => 'required|integer|min:2000|max:2100',
            'periodo_index' => 'required|integer|min:1|max:12',
            'clinica_id' => 'nullable|exists:clinicas,id',
            'estado' => 'nullable|in:pendiente,pagado',
        ]);

        $periodoMap = [
            'mensual' => 'month',
            'trimestral' => 'quarter',
            'semestral' => 'semester',
            'anual' => 'year',
        ];

        $periodoType = $periodoMap[$validated['periodo']];

        $repases = $this->balanceService->getRepasesDelPeriodo(
            (int) $validated['anio'],
            $periodoType,
            (int) $validated['periodo_index'],
            [
                'clinica_id' => $validated['clinica_id'] ?? null,
                'estado' => $validated['estado'] ?? null,
            ]
        );

        $clinicas = \App\Models\Clinica::orderBy('nombre')->get();

        return view('balances.detalle', [
            'repases' => $repases,
            'periodo' => $validated['periodo'],
            'anio' => $validated['anio'],
            'periodoIndex' => $validated['periodo_index'],
            'clinicas' => $clinicas,
            'filtros' => [
                'clinica_id' => $validated['clinica_id'] ?? null,
                'estado' => $validated['estado'] ?? null,
            ],
        ]);
    }

    protected function renderBalanceView(Request $request, string $viewName, string $periodoType): View
    {
        $validated = $request->validate([
            'fecha_inicio' => 'nullable|date|date_format:Y-m-d',
            'fecha_fin' => 'nullable|date|date_format:Y-m-d|after_or_equal:fecha_inicio',
            'clinica_id' => 'nullable|exists:clinicas,id',
            'estado' => 'nullable|in:pendiente,pagado',
        ]);

        $fechaInicio = $validated['fecha_inicio'] ?? now()->subYear()->startOfMonth()->format('Y-m-d');
        $fechaFin = $validated['fecha_fin'] ?? now()->format('Y-m-d');

        $filtros = [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'clinica_id' => $validated['clinica_id'] ?? null,
            'estado' => $validated['estado'] ?? null,
        ];

        $balances = $this->balanceService->getBalancesPorPeriodo($periodoType, $filtros);

        $gastosPorCategoria = $this->balanceService->getGastosPorCategoria($periodoType, $filtros);

        $topExamenes = $this->balanceService->getTopExamenes($periodoType, $filtros);

        $resumen = $this->balanceService->getResumenEjecutivo($filtros);

        $clinicas = \App\Models\Clinica::orderBy('nombre')->get();

        return view("balances.{$viewName}", [
            'balances' => $balances,
            'gastosPorCategoria' => $gastosPorCategoria,
            'topExamenes' => $topExamenes,
            'resumen' => $resumen,
            'filtros' => $filtros,
            'clinicas' => $clinicas,
        ]);
    }
}
