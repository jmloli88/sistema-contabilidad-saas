<?php

namespace Database\Seeders;

use App\Models\Empresa;
use App\Models\Examen;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ExamenSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * Crea exactamente 7 exámenes predefinidos con sus precios
     * según Requirements 3.2
     */
    public function run(): void
    {
        // Create or retrieve a default empresa for seed data
        $empresa = Empresa::firstOrCreate(['nombre' => 'Default Seed Empresa']);

        $examenes = [
            [
                'nombre' => 'Electroencefalograma c/ mapeamento 3d + foto estimulo',
                'precio_sin_nota' => 200.00,
                'precio_con_nota' => 220.00
            ],
            [
                'nombre' => 'Electroencefalograma c/ mapa',
                'precio_sin_nota' => 120.00,
                'precio_con_nota' => 140.00
            ],
            [
                'nombre' => 'Electroencefalograma',
                'precio_sin_nota' => 100.00,
                'precio_con_nota' => 120.00
            ],
            [
                'nombre' => 'Electroneuromiografia MEMBROS unilateral',
                'precio_sin_nota' => 150.00,
                'precio_con_nota' => 180.00
            ],
            [
                'nombre' => 'Electroneuromiografia FACIAL unilateral',
                'precio_sin_nota' => 170.00,
                'precio_con_nota' => 200.00
            ],
            [
                'nombre' => 'Potencial evocado VISUAL unilateral',
                'precio_sin_nota' => 146.00,
                'precio_con_nota' => 166.00
            ],
            [
                'nombre' => 'Potencial evocado AUDITIVO unilateral',
                'precio_sin_nota' => 146.00,
                'precio_con_nota' => 166.00
            ],
        ];

        foreach ($examenes as $examen) {
            $examen['empresa_id'] = $empresa->id;
            Examen::create($examen);
        }
    }
}
