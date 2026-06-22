<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Examen;
use Illuminate\Database\Seeder;

class ExamenSeeder extends Seeder
{
    /**
     * Seed the 7 default examenes for each empresa that has none yet.
     *
     * Idempotent: skips empresas that already have examenes.
     */
    public function run(): void
    {
        $empresas = Empresa::all();

        if ($empresas->isEmpty()) {
            $this->command->error('No hay empresas. Ejecutá EmpresaSeeder primero.');

            return;
        }

        $created = 0;
        foreach ($empresas as $empresa) {
            if ($empresa->examenes()->count() > 0) {
                continue;
            }

            foreach (Examen::defaults() as $data) {
                $empresa->examenes()->create($data);
                $created++;
            }
        }

        $this->command->info("{$created} exámenes creados (" . Examen::count() . ' en total).');
    }
}
