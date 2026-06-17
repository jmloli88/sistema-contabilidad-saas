<?php

namespace App\Http\Controllers;

use App\Models\Clinica;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador para el Dashboard del sistema
 * 
 * Este controlador maneja la visualización del dashboard principal,
 * mostrando métricas financieras y gráficos con la capacidad de
 * aplicar filtros por clínica, estado y rango de fechas.
 */
class DashboardController extends Controller
{
    /**
     * Servicio de Dashboard inyectado
     */
    private DashboardService $dashboardService;
    
    /**
     * Constructor del controlador
     * 
     * Inyecta el DashboardService
     * 
     * @param DashboardService $dashboardService
     */
    public function __construct(DashboardService $dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }
    
    /**
     * Muestra el dashboard con métricas y gráficos
     * 
     * Este método obtiene los filtros del request, calcula las métricas
     * financieras y prepara los datos para los gráficos. Los filtros
     * disponibles son:
     * - clinica_id: ID de la clínica para filtrar
     * - estado: Estado del repase (pendiente/pagado)
     * - fecha_desde: Fecha de inicio del rango
     * - fecha_hasta: Fecha de fin del rango
     * 
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        // Obtener filtros del request
        $filters = [
            'clinica_id' => $request->input('clinica_id'),
            'estado' => $request->input('estado'),
            'fecha_desde' => $request->input('fecha_desde'),
            'fecha_hasta' => $request->input('fecha_hasta'),
        ];
        
        // Obtener métricas del dashboard
        $metrics = $this->dashboardService->getMetrics($filters);
        
        // Obtener datos para los gráficos existentes
        $ingresosVsGastosChart = $this->dashboardService->getIngresosVsGastosChart($filters);
        $totalesPorClinicaChart = $this->dashboardService->getTotalesPorClinicaChart($filters);
        $pagadosVsPendientesChart = $this->dashboardService->getPagadosVsPendientesChart($filters);
        
        // Obtener datos para los nuevos gráficos
        $gastosPorCategoriaChart = $this->dashboardService->getGastosPorCategoriaChart($filters);
        $topExamenesChart = $this->dashboardService->getTopExamenesChart($filters);
        $evolucionIngresosNetosChart = $this->dashboardService->getEvolucionIngresosNetosChart($filters);
        $diasCobroPorClinicaChart = $this->dashboardService->getDiasCobroPorClinicaChart($filters);
        $margenGananciaPorClinicaChart = $this->dashboardService->getMargenGananciaPorClinicaChart($filters);
        $topClinicasConsultasChart = $this->dashboardService->getTopClinicasConsultasChart($filters);
        
        // Obtener todas las clínicas para el filtro
        $clinicas = Clinica::orderBy('nombre')->get();
        
        // Retornar vista con todos los datos
        return view('dashboard.index', [
            'metrics' => $metrics,
            'ingresosVsGastosChart' => $ingresosVsGastosChart,
            'totalesPorClinicaChart' => $totalesPorClinicaChart,
            'pagadosVsPendientesChart' => $pagadosVsPendientesChart,
            'gastosPorCategoriaChart' => $gastosPorCategoriaChart,
            'topExamenesChart' => $topExamenesChart,
            'evolucionIngresosNetosChart' => $evolucionIngresosNetosChart,
            'diasCobroPorClinicaChart' => $diasCobroPorClinicaChart,
            'margenGananciaPorClinicaChart' => $margenGananciaPorClinicaChart,
            'topClinicasConsultasChart' => $topClinicasConsultasChart,
            'filters' => $filters,
            'clinicas' => $clinicas,
        ]);
    }
}
