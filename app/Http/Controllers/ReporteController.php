<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

/**
 * Controlador para el Módulo de Reportes Avanzados
 * 
 * Este controlador maneja la generación de reportes financieros avanzados
 * incluyendo rentabilidad por clínica, rentabilidad por examen, productividad
 * y reportes comparativos. Solo accesible para usuarios administradores.
 * 
 * El middleware de autorización 'admin' se aplica en las rutas (routes/web.php)
 */
class ReporteController extends Controller
{
    /**
     * Servicio de reportes
     *
     * @var \App\Services\Reportes\ReporteService
     */
    protected $reporteService;

    /**
     * Constructor del controlador
     *
     * @param \App\Services\Reportes\ReporteService $reporteService
     */
    public function __construct(\App\Services\Reportes\ReporteService $reporteService)
    {
        $this->reporteService = $reporteService;
    }

    /**
     * Muestra el dashboard principal de reportes
     *
     * Presenta una interfaz centralizada con cards/botones para acceder
     * a cada tipo de reporte disponible:
     * - Rentabilidad por Clínica
     * - Rentabilidad por Examen
     * - Productividad
     * - Comparativo
     *
     * @return View
     */
    public function index(): View
    {
        return view('reportes.index');
    }

    /**
     * Genera reporte de comparación entre dos clínicas
     *
     * Compara métricas financieras de dos clínicas en el mismo período:
     * - Total de ingresos
     * - Total de gastos
     * - Ganancia neta
     * - Margen de ganancia
     * - Cantidad de repases
     * - Diferencias absolutas y porcentuales
     *
     * @param Request $request
     * @return View|\Illuminate\Http\RedirectResponse
     */
    public function comparacionClinicas(Request $request)
    {
        // Validar parámetros de entrada
        $validated = $request->validate([
            'fecha_inicio' => 'nullable|date|date_format:Y-m-d',
            'fecha_fin' => 'nullable|date|date_format:Y-m-d|after_or_equal:fecha_inicio',
            'clinica_1_id' => 'nullable|exists:clinicas,id',
            'clinica_2_id' => 'nullable|exists:clinicas,id|different:clinica_1_id',
        ], [
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_inicio.date_format' => 'La fecha de inicio debe tener el formato YYYY-MM-DD.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.date_format' => 'La fecha de fin debe tener el formato YYYY-MM-DD.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'clinica_1_id.exists' => 'La primera clínica seleccionada no existe.',
            'clinica_2_id.exists' => 'La segunda clínica seleccionada no existe.',
            'clinica_2_id.different' => 'Debes seleccionar dos clínicas diferentes.',
        ]);

        // Establecer valores por defecto
        $fechaInicio = $validated['fecha_inicio'] ?? now()->subYear()->startOfMonth()->format('Y-m-d');
        $fechaFin = $validated['fecha_fin'] ?? now()->format('Y-m-d');
        $clinica1Id = $validated['clinica_1_id'] ?? null;
        $clinica2Id = $validated['clinica_2_id'] ?? null;

        // Obtener todas las clínicas para los dropdowns
        $clinicas = \App\Models\Clinica::orderBy('nombre')->get();

        // Si no se han seleccionado ambas clínicas, mostrar formulario
        if (!$clinica1Id || !$clinica2Id) {
            return view('reportes.comparacion-clinicas', [
                'datos' => null,
                'filtros' => [
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin,
                    'clinica_1_id' => $clinica1Id,
                    'clinica_2_id' => $clinica2Id,
                ],
                'clinicas' => $clinicas,
            ]);
        }

        // Calcular datos comparativos
        $datos = $this->reporteService->compararClinicas(
            $clinica1Id,
            $clinica2Id,
            $fechaInicio,
            $fechaFin
        );

        // Verificar si hay datos
        if (!$datos['clinica_1'] || !$datos['clinica_2']) {
            return back()->with('warning', 'No se encontraron datos para las clínicas seleccionadas en el período especificado');
        }

        // Retornar vista con datos comparativos
        return view('reportes.comparacion-clinicas', [
            'datos' => $datos,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'clinica_1_id' => $clinica1Id,
                'clinica_2_id' => $clinica2Id,
            ],
            'clinicas' => $clinicas,
        ]);
    }

    /**
     * Genera reporte de rentabilidad por clínica
     *
     * Calcula métricas financieras agregadas por clínica incluyendo:
     * - Total de ingresos (exámenes + consultas)
     * - Total de gastos
     * - Ganancia neta
     * - Margen de ganancia
     * - Cantidad de repases
     *
     * Aplica filtros de fecha y clínica según los parámetros proporcionados.
     * Si no se proporcionan fechas, usa el mes actual por defecto.
     *
     * @param Request $request
     * @return View
     */
    public function rentabilidadClinica(Request $request)
    {
        // Validar parámetros de entrada
        $validated = $request->validate([
            'fecha_inicio' => 'nullable|date|date_format:Y-m-d',
            'fecha_fin' => 'nullable|date|date_format:Y-m-d|after_or_equal:fecha_inicio',
            'clinica_id' => 'nullable|exists:clinicas,id',
        ], [
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_inicio.date_format' => 'La fecha de inicio debe tener el formato YYYY-MM-DD.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.date_format' => 'La fecha de fin debe tener el formato YYYY-MM-DD.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'clinica_id.exists' => 'La clínica seleccionada no existe.',
        ]);

        // Establecer valores por defecto si no se proporcionan fechas
        $fechaInicio = $validated['fecha_inicio'] ?? now()->subYear()->startOfMonth()->format('Y-m-d');
        $fechaFin = $validated['fecha_fin'] ?? now()->format('Y-m-d');
        $clinicaId = $validated['clinica_id'] ?? null;

        // Preparar filtros para el servicio
        $filtros = [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ];

        if ($clinicaId) {
            $filtros['clinica_id'] = $clinicaId;
        }

        // Calcular datos del reporte
        $datos = $this->reporteService->calcularRentabilidadClinica($filtros);

        // Verificar si hay datos
        if ($datos->isEmpty() || $datos->sum('cantidad_repases') == 0) {
            return back()->with('warning', 'No se encontraron datos para los filtros seleccionados');
        }

        // Obtener todas las clínicas para el filtro dropdown
        $clinicas = \App\Models\Clinica::orderBy('nombre')->get();

        // Retornar vista con datos y filtros aplicados
        return view('reportes.rentabilidad-clinica', [
            'datos' => $datos,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'clinica_id' => $clinicaId,
            ],
            'clinicas' => $clinicas,
        ]);
    }

    /**
     * Genera reporte de rentabilidad por tipo de examen
     *
     * Calcula métricas financieras agregadas por tipo de examen incluyendo:
     * - Cantidad total de exámenes realizados
     * - Total de ingresos generados
     * - Ingreso promedio por examen
     *
     * Aplica filtros de fecha, clínica y examen según los parámetros proporcionados.
     * Si no se proporcionan fechas, usa el mes actual por defecto.
     * Los resultados se ordenan por total_ingresos en orden descendente.
     *
     * @param Request $request
     * @return View
     */
    public function rentabilidadExamen(Request $request): View
    {
        // Validar parámetros de entrada
        $validated = $request->validate([
            'fecha_inicio' => 'nullable|date|date_format:Y-m-d',
            'fecha_fin' => 'nullable|date|date_format:Y-m-d|after_or_equal:fecha_inicio',
            'clinica_id' => 'nullable|exists:clinicas,id',
            'examen_id' => 'nullable|exists:examenes,id',
        ], [
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_inicio.date_format' => 'La fecha de inicio debe tener el formato YYYY-MM-DD.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.date_format' => 'La fecha de fin debe tener el formato YYYY-MM-DD.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'clinica_id.exists' => 'La clínica seleccionada no existe.',
            'examen_id.exists' => 'El examen seleccionado no existe.',
        ]);

        // Establecer valores por defecto si no se proporcionan fechas
        $fechaInicio = $validated['fecha_inicio'] ?? now()->subYear()->startOfMonth()->format('Y-m-d');
        $fechaFin = $validated['fecha_fin'] ?? now()->format('Y-m-d');
        $clinicaId = $validated['clinica_id'] ?? null;
        $examenId = $validated['examen_id'] ?? null;

        // Preparar filtros para el servicio
        $filtros = [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ];

        if ($clinicaId) {
            $filtros['clinica_id'] = $clinicaId;
        }

        if ($examenId) {
            $filtros['examen_id'] = $examenId;
        }

        // Calcular datos del reporte (ya viene ordenado por total_ingresos DESC)
        $datos = $this->reporteService->calcularRentabilidadExamen($filtros);

        // Verificar si hay datos
        if ($datos->isEmpty() || $datos->sum('cantidad_total') == 0) {
            return back()->with('warning', 'No se encontraron datos para los filtros seleccionados');
        }

        // Obtener todas las clínicas para el filtro dropdown
        $clinicas = \App\Models\Clinica::orderBy('nombre')->get();

        // Obtener todos los exámenes para el filtro dropdown
        $examenes = \App\Models\Examen::orderBy('nombre')->get();

        // Retornar vista con datos y filtros aplicados
        return view('reportes.rentabilidad-examen', [
            'datos' => $datos,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'clinica_id' => $clinicaId,
                'examen_id' => $examenId,
            ],
            'clinicas' => $clinicas,
            'examenes' => $examenes,
        ]);
    }

    /**
     * Genera reporte de productividad
     *
     * Calcula métricas de cantidad de exámenes realizados incluyendo:
     * - Total de exámenes realizados
     * - Exámenes por día (promedio diario)
     * - Total de repases
     * - Exámenes por repase (promedio)
     * - Desglose por tipo de examen
     * - Desglose por clínica
     *
     * Aplica filtros de fecha y clínica según los parámetros proporcionados.
     * Si no se proporcionan fechas, usa el mes actual por defecto.
     *
     * @param Request $request
     * @return View|\Illuminate\Http\RedirectResponse
     */
    public function productividad(Request $request)
    {
        // Validar parámetros de entrada
        $validated = $request->validate([
            'fecha_inicio' => 'nullable|date|date_format:Y-m-d',
            'fecha_fin' => 'nullable|date|date_format:Y-m-d|after_or_equal:fecha_inicio',
            'clinica_id' => 'nullable|exists:clinicas,id',
        ], [
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_inicio.date_format' => 'La fecha de inicio debe tener el formato YYYY-MM-DD.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.date_format' => 'La fecha de fin debe tener el formato YYYY-MM-DD.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'clinica_id.exists' => 'La clínica seleccionada no existe.',
        ]);

        // Establecer valores por defecto si no se proporcionan fechas
        $fechaInicio = $validated['fecha_inicio'] ?? now()->subYear()->startOfMonth()->format('Y-m-d');
        $fechaFin = $validated['fecha_fin'] ?? now()->format('Y-m-d');
        $clinicaId = $validated['clinica_id'] ?? null;

        // Preparar filtros para el servicio
        $filtros = [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ];

        if ($clinicaId) {
            $filtros['clinica_id'] = $clinicaId;
        }

        // Calcular datos del reporte
        $datos = $this->reporteService->calcularProductividad($filtros);

        // Verificar si hay datos
        if ($datos['total_examenes_realizados'] == 0) {
            return back()->with('warning', 'No se encontraron datos para los filtros seleccionados');
        }

        // Obtener todas las clínicas para el filtro dropdown
        $clinicas = \App\Models\Clinica::orderBy('nombre')->get();

        // Retornar vista con datos y filtros aplicados
        return view('reportes.productividad', [
            'datos' => $datos,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'clinica_id' => $clinicaId,
            ],
            'clinicas' => $clinicas,
        ]);
    }

    /**
     * Genera reporte comparativo de períodos
     *
     * Compara métricas financieras entre dos períodos temporales:
     * - Total de ingresos
     * - Total de gastos
     * - Ganancia neta
     * - Variaciones porcentuales entre períodos
     *
     * Aplica filtros de fecha para ambos períodos y opcionalmente por clínica.
     * Si no se proporcionan fechas:
     * - Período actual: mes actual (primer día hasta hoy)
     * - Período anterior: mes anterior (primer día hasta último día)
     *
     * Maneja división por cero mostrando "N/A" para variaciones cuando
     * el valor del período anterior es cero.
     *
     * @param Request $request
     * @return View|\Illuminate\Http\RedirectResponse
     */
    public function comparativo(Request $request)
    {
        // Validar parámetros de entrada para ambos períodos
        $validated = $request->validate([
            'fecha_inicio_actual' => 'nullable|date|date_format:Y-m-d',
            'fecha_fin_actual' => 'nullable|date|date_format:Y-m-d|after_or_equal:fecha_inicio_actual',
            'fecha_inicio_anterior' => 'nullable|date|date_format:Y-m-d',
            'fecha_fin_anterior' => 'nullable|date|date_format:Y-m-d|after_or_equal:fecha_inicio_anterior',
            'clinica_id' => 'nullable|exists:clinicas,id',
        ], [
            'fecha_inicio_actual.date' => 'La fecha de inicio del período actual debe ser una fecha válida.',
            'fecha_inicio_actual.date_format' => 'La fecha de inicio del período actual debe tener el formato YYYY-MM-DD.',
            'fecha_fin_actual.date' => 'La fecha de fin del período actual debe ser una fecha válida.',
            'fecha_fin_actual.date_format' => 'La fecha de fin del período actual debe tener el formato YYYY-MM-DD.',
            'fecha_fin_actual.after_or_equal' => 'La fecha de fin del período actual debe ser posterior o igual a la fecha de inicio.',
            'fecha_inicio_anterior.date' => 'La fecha de inicio del período anterior debe ser una fecha válida.',
            'fecha_inicio_anterior.date_format' => 'La fecha de inicio del período anterior debe tener el formato YYYY-MM-DD.',
            'fecha_fin_anterior.date' => 'La fecha de fin del período anterior debe ser una fecha válida.',
            'fecha_fin_anterior.date_format' => 'La fecha de fin del período anterior debe tener el formato YYYY-MM-DD.',
            'fecha_fin_anterior.after_or_equal' => 'La fecha de fin del período anterior debe ser posterior o igual a la fecha de inicio.',
            'clinica_id.exists' => 'La clínica seleccionada no existe.',
        ]);

        // Establecer valores por defecto para período actual (mes actual)
        $fechaInicioActual = $validated['fecha_inicio_actual'] ?? now()->subYear()->startOfMonth()->format('Y-m-d');
        $fechaFinActual = $validated['fecha_fin_actual'] ?? now()->format('Y-m-d');

        // Establecer valores por defecto para período anterior (mes anterior completo)
        $fechaInicioAnterior = $validated['fecha_inicio_anterior'] ?? now()->subMonth()->startOfMonth()->format('Y-m-d');
        $fechaFinAnterior = $validated['fecha_fin_anterior'] ?? now()->subMonth()->endOfMonth()->format('Y-m-d');

        $clinicaId = $validated['clinica_id'] ?? null;

        // Preparar períodos para el servicio
        $periodoActual = [
            'fecha_inicio' => $fechaInicioActual,
            'fecha_fin' => $fechaFinActual,
        ];

        $periodoAnterior = [
            'fecha_inicio' => $fechaInicioAnterior,
            'fecha_fin' => $fechaFinAnterior,
        ];

        // Preparar filtros adicionales
        $filtros = [];
        if ($clinicaId) {
            $filtros['clinica_id'] = $clinicaId;
        }

        // Calcular datos comparativos
        $datos = $this->reporteService->calcularComparativo($periodoActual, $periodoAnterior, $filtros);

        // Verificar si hay datos en al menos uno de los períodos
        if ($datos['periodo_actual']['total_ingresos'] == 0 && $datos['periodo_anterior']['total_ingresos'] == 0) {
            return back()->with('warning', 'No se encontraron datos para los períodos seleccionados');
        }

        // Obtener todas las clínicas para el filtro dropdown
        $clinicas = \App\Models\Clinica::orderBy('nombre')->get();

        // Retornar vista con datos comparativos y filtros aplicados
        return view('reportes.comparativo', [
            'datos' => $datos,
            'filtros' => [
                'fecha_inicio_actual' => $fechaInicioActual,
                'fecha_fin_actual' => $fechaFinActual,
                'fecha_inicio_anterior' => $fechaInicioAnterior,
                'fecha_fin_anterior' => $fechaFinAnterior,
                'clinica_id' => $clinicaId,
            ],
            'clinicas' => $clinicas,
        ]);
    }

    /**
     * Genera reporte de análisis de consultas médicas
     *
     * Calcula métricas de consultas médicas incluyendo:
     * - Total de consultas realizadas
     * - Consultas por día (promedio diario)
     * - Total de repases
     * - Consultas por repase (promedio)
     * - Ratio consultas/exámenes
     * - Desglose por clínica con ranking
     * - Evolución mensual de consultas
     *
     * Aplica filtros de fecha y clínica según los parámetros proporcionados.
     * Si no se proporcionan fechas, usa el último año por defecto.
     *
     * @param Request $request
     * @return View|\Illuminate\Http\RedirectResponse
     */
    public function analisisConsultas(Request $request)
    {
        // Validar parámetros de entrada
        $validated = $request->validate([
            'fecha_inicio' => 'nullable|date|date_format:Y-m-d',
            'fecha_fin' => 'nullable|date|date_format:Y-m-d|after_or_equal:fecha_inicio',
            'clinica_id' => 'nullable|exists:clinicas,id',
        ], [
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
            'fecha_inicio.date_format' => 'La fecha de inicio debe tener el formato YYYY-MM-DD.',
            'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
            'fecha_fin.date_format' => 'La fecha de fin debe tener el formato YYYY-MM-DD.',
            'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
            'clinica_id.exists' => 'La clínica seleccionada no existe.',
        ]);

        // Establecer valores por defecto si no se proporcionan fechas
        $fechaInicio = $validated['fecha_inicio'] ?? now()->subYear()->startOfMonth()->format('Y-m-d');
        $fechaFin = $validated['fecha_fin'] ?? now()->format('Y-m-d');
        $clinicaId = $validated['clinica_id'] ?? null;

        // Preparar filtros para el servicio
        $filtros = [
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
        ];

        if ($clinicaId) {
            $filtros['clinica_id'] = $clinicaId;
        }

        // Calcular datos del reporte
        $datos = $this->reporteService->calcularAnalisisConsultas($filtros);

        // Verificar si hay datos
        if ($datos['total_consultas'] == 0) {
            return back()->with('warning', 'No se encontraron datos para los filtros seleccionados');
        }

        // Obtener todas las clínicas para el filtro dropdown
        $clinicas = \App\Models\Clinica::orderBy('nombre')->get();

        // Retornar vista con datos y filtros aplicados
        return view('reportes.analisis-consultas', [
            'datos' => $datos,
            'filtros' => [
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'clinica_id' => $clinicaId,
            ],
            'clinicas' => $clinicas,
        ]);
    }

    /**
     * Exporta reporte a Excel
     *
     * Valida los parámetros de entrada, regenera los datos del reporte según el tipo,
     * llama al ExportService para generar el archivo Excel, y retorna la descarga.
     * El archivo se elimina automáticamente después de ser enviado.
     *
     * Tipos de reporte soportados:
     * - rentabilidad-clinica: Requiere fecha_inicio, fecha_fin, opcional clinica_id
     * - rentabilidad-examen: Requiere fecha_inicio, fecha_fin, opcional clinica_id, opcional examen_id
     * - productividad: Requiere fecha_inicio, fecha_fin, opcional clinica_id
     * - comparativo: Requiere fecha_inicio_actual, fecha_fin_actual, fecha_inicio_anterior, fecha_fin_anterior, opcional clinica_id
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportExcel(Request $request)
    {
        try {
            // Validar parámetros comunes
            $validated = $request->validate([
                'tipo' => 'required|in:rentabilidad-clinica,rentabilidad-examen,productividad,comparativo,analisis-consultas,detalle-repases',
                'fecha_inicio' => 'required_unless:tipo,comparativo|date|date_format:Y-m-d',
                'fecha_fin' => 'required_unless:tipo,comparativo|date|date_format:Y-m-d|after_or_equal:fecha_inicio',
                'clinica_id' => 'nullable|exists:clinicas,id',
                'examen_id' => 'nullable|exists:examenes,id',
                'repase_ids' => 'nullable|array',
                'repase_ids.*' => 'integer|exists:repases,id',
                // Parámetros específicos para reporte comparativo
                'fecha_inicio_actual' => 'required_if:tipo,comparativo|date|date_format:Y-m-d',
                'fecha_fin_actual' => 'required_if:tipo,comparativo|date|date_format:Y-m-d|after_or_equal:fecha_inicio_actual',
                'fecha_inicio_anterior' => 'required_if:tipo,comparativo|date|date_format:Y-m-d',
                'fecha_fin_anterior' => 'required_if:tipo,comparativo|date|date_format:Y-m-d|after_or_equal:fecha_inicio_anterior',
            ], [
                'tipo.required' => 'El tipo de reporte es requerido.',
                'tipo.in' => 'El tipo de reporte no es válido.',
                'fecha_inicio.required_unless' => 'La fecha de inicio es requerida.',
                'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
                'fecha_inicio.date_format' => 'La fecha de inicio debe tener el formato YYYY-MM-DD.',
                'fecha_fin.required_unless' => 'La fecha de fin es requerida.',
                'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
                'fecha_fin.date_format' => 'La fecha de fin debe tener el formato YYYY-MM-DD.',
                'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
                'clinica_id.exists' => 'La clínica seleccionada no existe.',
                'examen_id.exists' => 'El examen seleccionado no existe.',
                'fecha_inicio_actual.required_if' => 'La fecha de inicio del período actual es requerida.',
                'fecha_inicio_actual.date' => 'La fecha de inicio del período actual debe ser una fecha válida.',
                'fecha_inicio_actual.date_format' => 'La fecha de inicio del período actual debe tener el formato YYYY-MM-DD.',
                'fecha_fin_actual.required_if' => 'La fecha de fin del período actual es requerida.',
                'fecha_fin_actual.date' => 'La fecha de fin del período actual debe ser una fecha válida.',
                'fecha_fin_actual.date_format' => 'La fecha de fin del período actual debe tener el formato YYYY-MM-DD.',
                'fecha_fin_actual.after_or_equal' => 'La fecha de fin del período actual debe ser posterior o igual a la fecha de inicio.',
                'fecha_inicio_anterior.required_if' => 'La fecha de inicio del período anterior es requerida.',
                'fecha_inicio_anterior.date' => 'La fecha de inicio del período anterior debe ser una fecha válida.',
                'fecha_inicio_anterior.date_format' => 'La fecha de inicio del período anterior debe tener el formato YYYY-MM-DD.',
                'fecha_fin_anterior.required_if' => 'La fecha de fin del período anterior es requerida.',
                'fecha_fin_anterior.date' => 'La fecha de fin del período anterior debe ser una fecha válida.',
                'fecha_fin_anterior.date_format' => 'La fecha de fin del período anterior debe tener el formato YYYY-MM-DD.',
                'fecha_fin_anterior.after_or_equal' => 'La fecha de fin del período anterior debe ser posterior o igual a la fecha de inicio.',
            ]);

            $tipo = $validated['tipo'];

            // Regenerar datos del reporte según el tipo
            $datos = $this->regenerarDatosReporte($tipo, $validated);

            // Preparar filtros para el export
            $filtros = $this->prepararFiltrosParaExport($tipo, $validated);

            // Llamar a ExportService para generar el archivo Excel
            $exportService = app(\App\Services\Reportes\ExportService::class);
            $rutaArchivo = $exportService->exportarExcel($tipo, $datos, $filtros);

            // Registrar exportación exitosa
            \Log::info('Exportación a Excel completada', [
                'tipo' => $tipo,
                'usuario_id' => auth()->id(),
                'filtros' => $filtros,
                'archivo' => basename($rutaArchivo),
            ]);

            // Retornar descarga con deleteFileAfterSend
            return response()->download($rutaArchivo)->deleteFileAfterSend(true);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-lanzar excepciones de validación para que Laravel las maneje
            throw $e;
        } catch (\Exception $e) {
            // Registrar error con contexto completo
            \Log::error('Error al exportar a Excel', [
                'tipo' => $request->input('tipo'),
                'usuario_id' => auth()->id(),
                'filtros' => $request->except(['_token']),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Ocurrió un error al generar el archivo Excel. Por favor, intenta nuevamente.');
        }
    }

    /**
     * Regenera los datos del reporte según el tipo
     *
     * @param string $tipo
     * @param array $validated
     * @return \Illuminate\Support\Collection|array
     */
    protected function regenerarDatosReporte(string $tipo, array $validated)
    {
        return match ($tipo) {
            'rentabilidad-clinica' => $this->regenerarRentabilidadClinica($validated),
            'rentabilidad-examen' => $this->regenerarRentabilidadExamen($validated),
            'productividad' => $this->regenerarProductividad($validated),
            'comparativo' => $this->regenerarComparativo($validated),
            'analisis-consultas' => $this->regenerarAnalisisConsultas($validated),
            'detalle-repases' => $this->regenerarDetalleRepases($validated),
            default => throw new \InvalidArgumentException("Tipo de reporte no válido: {$tipo}"),
        };
    }

    /**
     * Regenera datos de rentabilidad por clínica
     *
     * @param array $validated
     * @return \Illuminate\Support\Collection
     */
    protected function regenerarRentabilidadClinica(array $validated)
    {
        $filtros = [
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'],
        ];

        if (isset($validated['clinica_id'])) {
            $filtros['clinica_id'] = $validated['clinica_id'];
        }

        return $this->reporteService->calcularRentabilidadClinica($filtros);
    }

    /**
     * Regenera datos de rentabilidad por examen
     *
     * @param array $validated
     * @return \Illuminate\Support\Collection
     */
    protected function regenerarRentabilidadExamen(array $validated)
    {
        $filtros = [
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'],
        ];

        if (isset($validated['clinica_id'])) {
            $filtros['clinica_id'] = $validated['clinica_id'];
        }

        if (isset($validated['examen_id'])) {
            $filtros['examen_id'] = $validated['examen_id'];
        }

        return $this->reporteService->calcularRentabilidadExamen($filtros);
    }

    /**
     * Regenera datos de productividad
     *
     * @param array $validated
     * @return array
     */
    protected function regenerarProductividad(array $validated)
    {
        $filtros = [
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'],
        ];

        if (isset($validated['clinica_id'])) {
            $filtros['clinica_id'] = $validated['clinica_id'];
        }

        return $this->reporteService->calcularProductividad($filtros);
    }

    /**
     * Regenera datos comparativos
     *
     * @param array $validated
     * @return array
     */
    protected function regenerarComparativo(array $validated)
    {
        $periodoActual = [
            'fecha_inicio' => $validated['fecha_inicio_actual'],
            'fecha_fin' => $validated['fecha_fin_actual'],
        ];

        $periodoAnterior = [
            'fecha_inicio' => $validated['fecha_inicio_anterior'],
            'fecha_fin' => $validated['fecha_fin_anterior'],
        ];

        $filtros = [];
        if (isset($validated['clinica_id'])) {
            $filtros['clinica_id'] = $validated['clinica_id'];
        }

        return $this->reporteService->calcularComparativo($periodoActual, $periodoAnterior, $filtros);
    }

    /**
     * Regenera datos de análisis de consultas
     *
     * @param array $validated
     * @return array
     */
    protected function regenerarAnalisisConsultas(array $validated)
    {
        $filtros = [
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'],
        ];

        if (isset($validated['clinica_id'])) {
            $filtros['clinica_id'] = $validated['clinica_id'];
        }

        return $this->reporteService->calcularAnalisisConsultas($filtros);
    }

    protected function regenerarDetalleRepases(array $validated): Collection
    {
        $filtros = [
            'fecha_inicio' => $validated['fecha_inicio'],
            'fecha_fin' => $validated['fecha_fin'],
        ];

        if (isset($validated['clinica_id'])) {
            $filtros['clinica_id'] = $validated['clinica_id'];
        }

        $repases = $this->reporteService->getRepasesConGastos($filtros, false);

        if (!empty($validated['repase_ids'])) {
            $repases = $repases->whereIn('id', $validated['repase_ids']);
        }

        return $repases;
    }

    /**
     * Prepara los filtros para incluir en el archivo exportado
     *
     * @param string $tipo
     * @param array $validated
     * @return array
     */
    protected function prepararFiltrosParaExport(string $tipo, array $validated): array
    {
        $filtros = [];

        // Agregar fechas según el tipo de reporte
        if ($tipo === 'comparativo') {
            $filtros['fecha_inicio_actual'] = $validated['fecha_inicio_actual'];
            $filtros['fecha_fin_actual'] = $validated['fecha_fin_actual'];
            $filtros['fecha_inicio_anterior'] = $validated['fecha_inicio_anterior'];
            $filtros['fecha_fin_anterior'] = $validated['fecha_fin_anterior'];
        } else {
            $filtros['fecha_inicio'] = $validated['fecha_inicio'];
            $filtros['fecha_fin'] = $validated['fecha_fin'];
        }

        // Agregar nombre de clínica si está filtrado
        if (isset($validated['clinica_id'])) {
            $clinica = \App\Models\Clinica::find($validated['clinica_id']);
            if ($clinica) {
                $filtros['clinica_nombre'] = $clinica->nombre;
            }
        }

        // Agregar nombre de examen si está filtrado (solo para rentabilidad-examen)
        if ($tipo === 'rentabilidad-examen' && isset($validated['examen_id'])) {
            $examen = \App\Models\Examen::find($validated['examen_id']);
            if ($examen) {
                $filtros['examen_nombre'] = $examen->nombre;
            }
        }

        return $filtros;
    }

    /**
     * Exporta reporte a PDF
     *
     * Valida parámetros, regenera datos del reporte según tipo,
     * llama a ExportService.exportarPdf(), incluye gráficos como imágenes,
     * genera nombre de archivo con patrón reporte_{tipo}_{fecha}.pdf,
     * y retorna descarga con deleteFileAfterSend.
     *
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function exportPdf(Request $request)
    {
        try {
            // Validar parámetros comunes (misma validación que exportExcel)
            $validated = $request->validate([
                'tipo' => 'required|in:rentabilidad-clinica,rentabilidad-examen,productividad,comparativo,analisis-consultas,detalle-repases',
                'fecha_inicio' => 'required_unless:tipo,comparativo|date|date_format:Y-m-d',
                'fecha_fin' => 'required_unless:tipo,comparativo|date|date_format:Y-m-d|after_or_equal:fecha_inicio',
                'clinica_id' => 'nullable|exists:clinicas,id',
                'examen_id' => 'nullable|exists:examenes,id',
                // Parámetros específicos para reporte comparativo
                'fecha_inicio_actual' => 'required_if:tipo,comparativo|date|date_format:Y-m-d',
                'fecha_fin_actual' => 'required_if:tipo,comparativo|date|date_format:Y-m-d|after_or_equal:fecha_inicio_actual',
                'fecha_inicio_anterior' => 'required_if:tipo,comparativo|date|date_format:Y-m-d',
                'fecha_fin_anterior' => 'required_if:tipo,comparativo|date|date_format:Y-m-d|after_or_equal:fecha_inicio_anterior',
                // Parámetro opcional para gráficos
                'graficos' => 'nullable|array',
                'graficos.*' => 'nullable|string',
            ], [
                'tipo.required' => 'El tipo de reporte es requerido.',
                'tipo.in' => 'El tipo de reporte no es válido.',
                'fecha_inicio.required_unless' => 'La fecha de inicio es requerida.',
                'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida.',
                'fecha_inicio.date_format' => 'La fecha de inicio debe tener el formato YYYY-MM-DD.',
                'fecha_fin.required_unless' => 'La fecha de fin es requerida.',
                'fecha_fin.date' => 'La fecha de fin debe ser una fecha válida.',
                'fecha_fin.date_format' => 'La fecha de fin debe tener el formato YYYY-MM-DD.',
                'fecha_fin.after_or_equal' => 'La fecha de fin debe ser posterior o igual a la fecha de inicio.',
                'clinica_id.exists' => 'La clínica seleccionada no existe.',
                'examen_id.exists' => 'El examen seleccionado no existe.',
                'fecha_inicio_actual.required_if' => 'La fecha de inicio del período actual es requerida.',
                'fecha_inicio_actual.date' => 'La fecha de inicio del período actual debe ser una fecha válida.',
                'fecha_inicio_actual.date_format' => 'La fecha de inicio del período actual debe tener el formato YYYY-MM-DD.',
                'fecha_fin_actual.required_if' => 'La fecha de fin del período actual es requerida.',
                'fecha_fin_actual.date' => 'La fecha de fin del período actual debe ser una fecha válida.',
                'fecha_fin_actual.date_format' => 'La fecha de fin del período actual debe tener el formato YYYY-MM-DD.',
                'fecha_fin_actual.after_or_equal' => 'La fecha de fin del período actual debe ser posterior o igual a la fecha de inicio.',
                'fecha_inicio_anterior.required_if' => 'La fecha de inicio del período anterior es requerida.',
                'fecha_inicio_anterior.date' => 'La fecha de inicio del período anterior debe ser una fecha válida.',
                'fecha_inicio_anterior.date_format' => 'La fecha de inicio del período anterior debe tener el formato YYYY-MM-DD.',
                'fecha_fin_anterior.required_if' => 'La fecha de fin del período anterior es requerida.',
                'fecha_fin_anterior.date' => 'La fecha de fin del período anterior debe ser una fecha válida.',
                'fecha_fin_anterior.date_format' => 'La fecha de fin del período anterior debe tener el formato YYYY-MM-DD.',
                'fecha_fin_anterior.after_or_equal' => 'La fecha de fin del período anterior debe ser posterior o igual a la fecha de inicio.',
                'graficos.array' => 'Los gráficos deben ser un array.',
            ]);

            $tipo = $validated['tipo'];

            // Regenerar datos del reporte según el tipo
            $datos = $this->regenerarDatosReporte($tipo, $validated);

            // Preparar filtros para el export
            $filtros = $this->prepararFiltrosParaExport($tipo, $validated);

            // Obtener gráficos si fueron proporcionados
            $graficos = $validated['graficos'] ?? [];

            // Llamar a ExportService para generar el archivo PDF
            $exportService = app(\App\Services\Reportes\ExportService::class);
            $rutaArchivo = $exportService->exportarPdf($tipo, $datos, $filtros, $graficos);

            // Registrar exportación exitosa
            \Log::info('Exportación a PDF completada', [
                'tipo' => $tipo,
                'usuario_id' => auth()->id(),
                'filtros' => $filtros,
                'archivo' => basename($rutaArchivo),
                'incluye_graficos' => !empty($graficos),
            ]);

            // Retornar descarga con deleteFileAfterSend
            return response()->download($rutaArchivo)->deleteFileAfterSend(true);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-lanzar excepciones de validación para que Laravel las maneje
            throw $e;
        } catch (\Exception $e) {
            // Registrar error con contexto completo
            \Log::error('Error al exportar a PDF', [
                'tipo' => $request->input('tipo'),
                'usuario_id' => auth()->id(),
                'filtros' => $request->except(['_token']),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->with('error', 'Ocurrió un error al generar el archivo PDF. Por favor, intenta nuevamente.');
        }
    }


}

