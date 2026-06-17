<?php

namespace App\Http\Controllers;

use App\Models\Repase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Controlador para la vista de calendario de repases.
 * 
 * Muestra los repases en un calendario mensual usando FullCalendar,
 * con color coding según el estado (rojo: pendiente, verde: pagado).
 */
class CalendarioController extends Controller
{
    /**
     * Muestra la vista del calendario.
     * 
     * @param Request $request
     * @return View
     */
    public function index(Request $request): View
    {
        // Obtener todas las clínicas para el filtro
        $clinicas = \App\Models\Clinica::orderBy('nombre')->get();
        
        return view('calendario.index', compact('clinicas'));
    }

    /**
     * Retorna los eventos del calendario en formato FullCalendar.
     * 
     * Aplica filtros de clínica si se proporciona.
     * Formatea eventos con color según estado:
     * - Rojo (#ef4444): pendiente - muestra en fecha programada de pago
     * - Verde (#22c55e): pagado - muestra en fecha de pago realizado
     * 
     * Lógica de fechas:
     * - Si tiene fecha_pago: se muestra en esa fecha (sea pendiente o pagado)
     * - Si NO tiene fecha_pago: se muestra en la fecha del repase
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function events(Request $request): JsonResponse
    {
        // Construir query base con eager loading
        $query = Repase::with('clinica');

        // Aplicar filtro de clínica si se proporciona
        if ($request->has('clinica_id') && $request->clinica_id) {
            $query->byClinica($request->clinica_id);
        }

        // Obtener repases
        $repases = $query->get();

        // Formatear eventos para FullCalendar
        $events = $repases->map(function ($repase) {
            // Determinar la fecha a mostrar en el calendario
            // Si tiene fecha_pago, usar esa fecha (sea pendiente o pagado)
            // Si no tiene fecha_pago, usar la fecha del repase
            $fechaCalendario = $repase->fecha_pago 
                ? $repase->fecha_pago->format('Y-m-d')
                : $repase->fecha->format('Y-m-d');
            
            // Determinar el título según el estado
            $estadoTexto = $repase->estado === 'pendiente' ? 'Pendiente' : 'Pagado';
            
            return [
                'id' => $repase->id,
                'title' => $repase->clinica->nombre . ' - R$' . number_format($repase->total_neto, 2) . ' (' . $estadoTexto . ')',
                'start' => $fechaCalendario,
                'backgroundColor' => $repase->estado === 'pendiente' ? '#ef4444' : '#22c55e',
                'borderColor' => $repase->estado === 'pendiente' ? '#dc2626' : '#16a34a',
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'clinica' => $repase->clinica->nombre,
                    'estado' => $repase->estado,
                    'fecha_repase' => $repase->fecha->format('Y-m-d'),
                    'fecha_pago' => $repase->fecha_pago?->format('Y-m-d'),
                    'total_neto' => $repase->total_neto,
                    'total_examenes' => $repase->total_examenes,
                    'total_consultas' => $repase->total_consultas,
                    'total_gastos' => $repase->total_gastos,
                ],
                'url' => route('repases.show', $repase->id),
            ];
        });

        return response()->json($events);
    }
}
