<?php

namespace Database\Seeders;

use App\Models\Agenda;
use App\Models\Clinica;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AgendaSeeder extends Seeder
{
    /**
     * Seed agendas with explicit empresa_id (inherited from each clinica).
     *
     * Idempotent: skips clinicas that already have agendas.
     */
    public function run(): void
    {
        $clinicas = Clinica::all();

        if ($clinicas->isEmpty()) {
            $this->command->error('No hay clínicas. Ejecutá ClinicaSeeder primero.');

            return;
        }

        $created = 0;

        foreach ($clinicas->take(3) as $clinica) {
            if (Agenda::where('clinica_id', $clinica->id)->exists()) {
                continue;
            }

            Agenda::create([
                'empresa_id' => $clinica->empresa_id,
                'clinica_id' => $clinica->id,
                'fecha' => Carbon::now()->addDays(rand(1, 30)),
                'hora_inicio' => '08:00',
                'hora_fin' => '13:00',
                'doctor' => 'Dr. ' . fake()->lastName(),
                'tipo_repeticion' => 'unica',
            ]);
            $created++;
        }

        // Una agenda repetitiva semanal para la primera clínica.
        $primera = $clinicas->first();
        if ($primera && ! Agenda::where('clinica_id', $primera->id)->where('tipo_repeticion', 'repetitiva')->exists()) {
            Agenda::crearAgendasRepetitivas([
                'empresa_id' => $primera->empresa_id,
                'clinica_id' => $primera->id,
                'fecha' => Carbon::now()->addDays(2)->format('Y-m-d'),
                'hora_inicio' => '14:00',
                'hora_fin' => '18:00',
                'doctor' => 'Dr. García',
                'dias_repeticion' => 7,
            ]);
            $created++;
        }

        $this->command->info("{$created} agendas nuevas creadas (total: " . Agenda::count() . ').');
    }
}
