<?php

namespace App\Http\Controllers;

use App\Models\Agenda;
use App\Models\Clinica;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class AgendaController extends Controller
{
    /** Fixed color palette — one colour per clinic so you recognize it at a glance. */
    private const CLINIC_COLORS = [
        '#4f46e5', // indigo
        '#0891b2', // cyan
        '#059669', // emerald
        '#d97706', // amber
        '#dc2626', // red
        '#7c3aed', // violet
        '#0d9488', // teal
        '#c026d3', // fuchsia
    ];

    /** Resolve a stable background colour for a clinic (cycles if > 8 clinics). */
    private static function clinicColor(int $clinicaId): string
    {
        $idx = ($clinicaId - 1) % count(self::CLINIC_COLORS);

        return self::CLINIC_COLORS[$idx];
    }
    public function index(): View
    {
        $clinicas = Clinica::orderBy('nombre')->get();
        return view('agendas.index', compact('clinicas'));
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'clinica_id' => 'required|exists:clinicas,id',
                'fecha' => 'required|date',
                'hora_inicio' => 'required|date_format:H:i',
                'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
                'doctor' => 'required|string|max:255',
                'tipo_repeticion' => 'required|in:unica,repetitiva',
                'dias_repeticion' => 'nullable|integer|min:1',
                'frecuencia_mensual' => 'nullable|boolean',
            ]);

            // Validación adicional: si es repetitiva, debe tener dias_repeticion o frecuencia_mensual
            if ($validated['tipo_repeticion'] === 'repetitiva') {
                if (empty($validated['dias_repeticion']) && empty($validated['frecuencia_mensual'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Debe especificar la frecuencia de repetición'
                    ], 422);
                }
            }
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        }

        try {
            if ($validated['tipo_repeticion'] === 'unica') {
                // Verificar conflicto para agenda única
                if (Agenda::tieneConflictoHorario(
                    $validated['clinica_id'],
                    $validated['fecha'],
                    $validated['hora_inicio'],
                    $validated['hora_fin']
                )) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ya existe una agenda en ese horario para la fecha seleccionada.'
                    ], 422);
                }

                $agenda = Agenda::create($validated);

                return response()->json([
                    'success' => true,
                    'message' => 'Agenda creada exitosamente',
                    'agenda' => $agenda->load('clinica')
                ]);
            } else {
                // Crear agendas repetitivas
                $resultado = Agenda::crearAgendasRepetitivas($validated);

                if (empty($resultado['agendas'])) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No se pudo crear ninguna agenda debido a conflictos de horario.',
                        'conflictos' => $resultado['conflictos']
                    ], 422);
                }

                $mensaje = count($resultado['agendas']) . ' agendas creadas exitosamente';
                if (!empty($resultado['conflictos'])) {
                    $mensaje .= '. ' . count($resultado['conflictos']) . ' fechas omitidas por conflictos.';
                }

                return response()->json([
                    'success' => true,
                    'message' => $mensaje,
                    'agendas_creadas' => count($resultado['agendas']),
                    'conflictos' => $resultado['conflictos']
                ]);
            }
        } catch (\Exception $e) {
            \Log::error('Error al crear agenda: ' . $e->getMessage(), [
                'exception' => $e,
                'request' => $request->all()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la agenda: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, Agenda $agenda): JsonResponse
    {
        $validated = $request->validate([
            'clinica_id' => 'required|exists:clinicas,id',
            'fecha' => 'required|date',
            'hora_inicio' => 'required|date_format:H:i',
            'hora_fin' => 'required|date_format:H:i|after:hora_inicio',
            'doctor' => 'required|string|max:255',
            'aplicar_a_todas' => 'required|boolean',
        ]);

        $aplicarATodas = $validated['aplicar_a_todas'];
        unset($validated['aplicar_a_todas']);

        // Verificar conflicto de horario
        if (Agenda::tieneConflictoHorario(
            $validated['clinica_id'],
            $validated['fecha'],
            $validated['hora_inicio'],
            $validated['hora_fin'],
            $agenda->id
        )) {
            return response()->json([
                'success' => false,
                'message' => 'Ya existe una agenda en ese horario.'
            ], 422);
        }

        if ($aplicarATodas && $agenda->grupo_repeticion) {
            // Actualizar todas las agendas del grupo
            Agenda::where('grupo_repeticion', $agenda->grupo_repeticion)
                ->update([
                    'clinica_id' => $validated['clinica_id'],
                    'hora_inicio' => $validated['hora_inicio'],
                    'hora_fin' => $validated['hora_fin'],
                    'doctor' => $validated['doctor'],
                ]);

            return response()->json([
                'success' => true,
                'message' => 'Todas las agendas del grupo actualizadas exitosamente'
            ]);
        } else {
            // Actualizar solo esta agenda
            $agenda->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Agenda actualizada exitosamente',
                'agenda' => $agenda->load('clinica')
            ]);
        }
    }

    public function destroy(Request $request, Agenda $agenda): JsonResponse
    {
        $eliminarTodas = $request->boolean('eliminar_todas');

        if ($eliminarTodas && $agenda->grupo_repeticion) {
            $count = Agenda::where('grupo_repeticion', $agenda->grupo_repeticion)->count();
            Agenda::where('grupo_repeticion', $agenda->grupo_repeticion)->delete();

            return response()->json([
                'success' => true,
                'message' => "{$count} agendas eliminadas exitosamente"
            ]);
        } else {
            $agenda->delete();

            return response()->json([
                'success' => true,
                'message' => 'Agenda eliminada exitosamente'
            ]);
        }
    }

    public function events(Request $request): JsonResponse
    {
        $query = Agenda::with('clinica');

        if ($request->has('clinica_id') && $request->clinica_id) {
            $query->where('clinica_id', $request->clinica_id);
        }

        if ($request->has('start') && $request->has('end')) {
            $query->whereDate('fecha', '>=', $request->start)
                  ->whereDate('fecha', '<=', $request->end);
        }

        $agendas = $query->with('googleCalendarEvent')->get();

        $events = $agendas->map(function ($agenda) {
            $color = self::clinicColor($agenda->clinica_id);
            $esRepetitiva = $agenda->tipo_repeticion === 'repetitiva';

            return [
                'id' => $agenda->id,
                'title' => $agenda->clinica->nombre . "\n" . 
                          $agenda->hora_inicio . ' - ' . $agenda->hora_fin . "\n" . 
                          'Dr. ' . $agenda->doctor,
                'start' => $agenda->fecha->format('Y-m-d'),
                'backgroundColor' => $color,
                'borderColor' => $color,
                'textColor' => '#ffffff',
                'extendedProps' => [
                    'clinica_id' => $agenda->clinica_id,
                    'clinica' => $agenda->clinica->nombre,
                    'hora_inicio' => $agenda->hora_inicio,
                    'hora_fin' => $agenda->hora_fin,
                    'doctor' => $agenda->doctor,
                    'tipo_repeticion' => $agenda->tipo_repeticion,
                    'grupo_repeticion' => $agenda->grupo_repeticion,
                    'google_synced' => $agenda->googleCalendarEvent !== null,
                    'color' => $color,
                    'repetitiva' => $esRepetitiva,
                ],
            ];
        });

        return response()->json($events);
    }
}
