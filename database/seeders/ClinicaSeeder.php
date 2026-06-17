<?php

namespace Database\Seeders;

use App\Models\Clinica;
use App\Models\Empresa;
use Illuminate\Database\Seeder;

class ClinicaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empresa = Empresa::firstOrCreate(['nombre' => 'Default Seed Empresa']);

        $clinicas = [
            [
                'nombre' => 'Clínica San José',
                'direccion' => 'Av. Principal 123, Lima',
                'telefono' => '01-234-5678',
            ],
            [
                'nombre' => 'Centro Médico Santa Rosa',
                'direccion' => 'Jr. Los Olivos 456, Lima',
                'telefono' => '01-345-6789',
            ],
            [
                'nombre' => 'Hospital Regional',
                'direccion' => 'Av. Salud 789, Lima',
                'telefono' => '01-456-7890',
            ],
            [
                'nombre' => 'Clínica del Norte',
                'direccion' => 'Calle Norte 321, Lima',
                'telefono' => '01-567-8901',
            ],
            [
                'nombre' => 'Centro de Diagnóstico Integral',
                'direccion' => 'Av. Diagnóstico 654, Lima',
                'telefono' => '01-678-9012',
            ],
        ];

        foreach ($clinicas as $clinica) {
            $clinica['empresa_id'] = $empresa->id;
            Clinica::create($clinica);
        }
    }
}
