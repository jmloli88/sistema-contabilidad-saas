<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Agenda extends Model
{
    use HasFactory;

    protected $fillable = [
        'clinica_id',
        'fecha',
        'hora_inicio',
        'hora_fin',
        'doctor',
        'tipo_repeticion',
        'dias_repeticion',
        'grupo_repeticion',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function clinica(): BelongsTo
    {
        return $this->belongsTo(Clinica::class);
    }

    /**
     * Verificar si hay conflicto de horario en una fecha específica
     * Dos rangos de tiempo se solapan si:
     * - El inicio del nuevo rango está antes del fin del existente Y
     * - El fin del nuevo rango está después del inicio del existente
     */
    public static function tieneConflictoHorario(
        int $clinicaId,
        string $fecha,
        string $horaInicio,
        string $horaFin,
        ?int $agendaIdExcluir = null
    ): bool {
        $query = self::where('clinica_id', $clinicaId)
            ->whereDate('fecha', $fecha)
            ->where(function ($q) use ($horaInicio, $horaFin) {
                // Hay solapamiento si el nuevo inicio es antes del fin existente
                // Y el nuevo fin es después del inicio existente
                $q->where('hora_inicio', '<', $horaFin)
                  ->where('hora_fin', '>', $horaInicio);
            });

        if ($agendaIdExcluir) {
            $query->where('id', '!=', $agendaIdExcluir);
        }

        return $query->exists();
    }

    /**
     * Crear agendas repetitivas hasta fin de año
     */
    public static function crearAgendasRepetitivas(array $datos): array
    {
        $grupoId = uniqid('grupo_', true);
        $fechaInicio = Carbon::parse($datos['fecha']);
        $finAnio = Carbon::create($fechaInicio->year, 12, 31);
        $agendasCreadas = [];
        $conflictos = [];

        $esMensual = isset($datos['frecuencia_mensual']) && $datos['frecuencia_mensual'];

        if ($esMensual) {
            // Repetición mensual: mismo día de la semana, misma semana del mes
            $diaSemanaInicial = $fechaInicio->dayOfWeek; // 0=domingo, 1=lunes, ..., 6=sábado
            $semanaDelMes = (int) ceil($fechaInicio->day / 7); // 1=primera semana, 2=segunda, etc.
            
            $fechaActual = $fechaInicio->copy();
            
            while ($fechaActual->lte($finAnio)) {
                // Si cae en domingo, mover al lunes
                if ($fechaActual->isSunday()) {
                    $fechaActual->addDay();
                }

                // Verificar conflicto de horario
                if (self::tieneConflictoHorario(
                    $datos['clinica_id'],
                    $fechaActual->format('Y-m-d'),
                    $datos['hora_inicio'],
                    $datos['hora_fin']
                )) {
                    $conflictos[] = [
                        'fecha' => $fechaActual->format('Y-m-d'),
                        'mensaje' => "Conflicto de horario en {$fechaActual->format('d/m/Y')}"
                    ];
                } else {
                    $agenda = self::create([
                        'clinica_id' => $datos['clinica_id'],
                        'fecha' => $fechaActual->format('Y-m-d'),
                        'hora_inicio' => $datos['hora_inicio'],
                        'hora_fin' => $datos['hora_fin'],
                        'doctor' => $datos['doctor'],
                        'tipo_repeticion' => 'repetitiva',
                        'dias_repeticion' => null,
                        'grupo_repeticion' => $grupoId,
                    ]);
                    $agendasCreadas[] = $agenda;
                }

                // Calcular la siguiente fecha mensual
                $fechaActual->addMonth();
                
                // Encontrar el mismo día de la semana en la misma semana del mes
                $primerDiaDelMes = $fechaActual->copy()->startOfMonth();
                $primerOcurrencia = $primerDiaDelMes->copy()->next($diaSemanaInicial);
                
                // Si el primer día del mes ya es el día de la semana buscado
                if ($primerDiaDelMes->dayOfWeek === $diaSemanaInicial) {
                    $primerOcurrencia = $primerDiaDelMes;
                }
                
                // Calcular la fecha objetivo sumando las semanas necesarias
                $fechaActual = $primerOcurrencia->copy()->addWeeks($semanaDelMes - 1);
                
                // Si la fecha calculada se pasa al siguiente mes, usar la última ocurrencia del mes anterior
                if ($fechaActual->month !== $primerDiaDelMes->month) {
                    $fechaActual = $primerOcurrencia->copy()->addWeeks($semanaDelMes - 2);
                }
            }
        } else {
            // Repetición por días (semanal, quincenal, personalizado)
            $fechaActual = $fechaInicio->copy();

            while ($fechaActual->lte($finAnio)) {
                // Si cae en domingo, mover al lunes
                if ($fechaActual->isSunday()) {
                    $fechaActual->addDay();
                }

                // Verificar conflicto de horario
                if (self::tieneConflictoHorario(
                    $datos['clinica_id'],
                    $fechaActual->format('Y-m-d'),
                    $datos['hora_inicio'],
                    $datos['hora_fin']
                )) {
                    $conflictos[] = [
                        'fecha' => $fechaActual->format('Y-m-d'),
                        'mensaje' => "Conflicto de horario en {$fechaActual->format('d/m/Y')}"
                    ];
                } else {
                    $agenda = self::create([
                        'clinica_id' => $datos['clinica_id'],
                        'fecha' => $fechaActual->format('Y-m-d'),
                        'hora_inicio' => $datos['hora_inicio'],
                        'hora_fin' => $datos['hora_fin'],
                        'doctor' => $datos['doctor'],
                        'tipo_repeticion' => 'repetitiva',
                        'dias_repeticion' => $datos['dias_repeticion'],
                        'grupo_repeticion' => $grupoId,
                    ]);
                    $agendasCreadas[] = $agenda;
                }

                $fechaActual->addDays((int) $datos['dias_repeticion']);
            }
        }

        return [
            'agendas' => $agendasCreadas,
            'conflictos' => $conflictos,
            'grupo_id' => $grupoId
        ];
    }
}
