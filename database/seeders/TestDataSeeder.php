<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Clinica;
use App\Models\Repase;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Crear clínicas de prueba
        $clinica1 = Clinica::create([
            'nombre' => 'Clínica Central',
            'direccion' => 'Av. Principal 123',
            'telefono' => '555-0001'
        ]);

        $clinica2 = Clinica::create([
            'nombre' => 'Clínica Norte',
            'direccion' => 'Calle Norte 456',
            'telefono' => '555-0002'
        ]);

        $clinica3 = Clinica::create([
            'nombre' => 'Clínica Sur',
            'direccion' => 'Av. Sur 789',
            'telefono' => '555-0003'
        ]);

        // Crear repases de prueba para enero 2024
        Repase::create([
            'clinica_id' => $clinica1->id,
            'fecha' => '2024-01-15',
            'fecha_pago' => '2024-01-20',
            'estado' => 'pagado',
            'tipo_precio' => 'con_nota',
            'total_examenes' => 440.00,
            'total_consultas' => 25,
            'total_gastos' => 150.00,
            'total_neto' => 290.00,
        ]);

        Repase::create([
            'clinica_id' => $clinica2->id,
            'fecha' => '2024-01-20',
            'estado' => 'pendiente',
            'tipo_precio' => 'sin_nota',
            'total_examenes' => 300.00,
            'total_consultas' => 18,
            'total_gastos' => 100.00,
            'total_neto' => 200.00,
        ]);

        Repase::create([
            'clinica_id' => $clinica3->id,
            'fecha' => '2024-01-25',
            'fecha_pago' => '2024-01-30',
            'estado' => 'pagado',
            'tipo_precio' => 'con_nota',
            'total_examenes' => 550.00,
            'total_consultas' => 32,
            'total_gastos' => 180.00,
            'total_neto' => 370.00,
        ]);

        // Crear repases de prueba para febrero 2024
        Repase::create([
            'clinica_id' => $clinica1->id,
            'fecha' => '2024-02-10',
            'fecha_pago' => '2024-02-15',
            'estado' => 'pagado',
            'tipo_precio' => 'con_nota',
            'total_examenes' => 660.00,
            'total_consultas' => 40,
            'total_gastos' => 200.00,
            'total_neto' => 460.00,
        ]);

        Repase::create([
            'clinica_id' => $clinica2->id,
            'fecha' => '2024-02-15',
            'estado' => 'pendiente',
            'tipo_precio' => 'sin_nota',
            'total_examenes' => 400.00,
            'total_consultas' => 12,
            'total_gastos' => 120.00,
            'total_neto' => 280.00,
        ]);

        Repase::create([
            'clinica_id' => $clinica3->id,
            'fecha' => '2024-02-20',
            'estado' => 'pendiente',
            'tipo_precio' => 'sin_nota',
            'total_examenes' => 350.00,
            'total_consultas' => 22,
            'total_gastos' => 90.00,
            'total_neto' => 260.00,
        ]);

        // Crear repases de prueba para marzo 2024
        Repase::create([
            'clinica_id' => $clinica1->id,
            'fecha' => '2024-03-05',
            'fecha_pago' => '2024-03-10',
            'estado' => 'pagado',
            'tipo_precio' => 'con_nota',
            'total_examenes' => 880.00,
            'total_consultas' => 55,
            'total_gastos' => 250.00,
            'total_neto' => 630.00,
        ]);

        Repase::create([
            'clinica_id' => $clinica2->id,
            'fecha' => '2024-03-12',
            'fecha_pago' => '2024-03-18',
            'estado' => 'pagado',
            'tipo_precio' => 'con_nota',
            'total_examenes' => 720.00,
            'total_consultas' => 48,
            'total_gastos' => 220.00,
            'total_neto' => 500.00,
        ]);
    }
}
