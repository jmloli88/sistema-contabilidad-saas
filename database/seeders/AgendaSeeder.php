<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Clinica;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AgendaSeeder extends Seeder
{
    public function run(): void
    {
        $clinicas = Clinica::all();

        if ($clinicas->isEmpty()) {
            $this->command->warn('No hay clínicas en la base de datos. Ejecuta primero el seeder de clínicas.');
            return;
        }

        // Crear algunas agendas únicas
        foreach ($clinicas->take(3) as $clinica) {
            Agenda::create([
                'clinica_id' => $clinica->id,
                'fecha' => Carbon::now()->addDays(rand(1, 30)),
                'hora_inicio' => '08:00',
                'hora_fin' => '13:00',
                'doctor' => 'Dr. ' . fake()->lastName(),
                'tipo_repeticion' => 'unica',
            ]);
        }

        // Crear una agenda repetitiva semanal
        if ($clinicas->count() > 0) {
            $datos = [
                'clinica_id' => $clinicas->first()->id,
                'fecha' => Carbon::now()->addDays(2)->format('Y-m-d'),
                'hora_inicio' => '14:00',
                'hora_fin' => '18:00',
                'doctor' => 'Dr. García',
                'dias_repeticion' => 7,
            ];

            Agenda::crearAgendasRepetitivas($datos);
        }

        $this->command->info('Agendas creadas exitosamente.');
    }
}
