<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Examen;
use Illuminate\Database\Seeder;

class ExamenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea los 7 exámenes predefinidos para cada empresa existente
     * que aún no tenga exámenes creados.
     */
    public function run(): void
    {
        $empresas = Empresa::all();

        foreach ($empresas as $empresa) {
            if ($empresa->examenes()->count() === 0) {
                foreach (Examen::defaults() as $exam) {
                    $empresa->examenes()->create($exam);
                }
            }
        }
    }
}
