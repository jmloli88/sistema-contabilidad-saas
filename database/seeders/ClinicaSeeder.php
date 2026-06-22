<?php

namespace Database\Seeders;

use App\Models\Clinica;
use App\Models\Empresa;
use Illuminate\Database\Seeder;

class ClinicaSeeder extends Seeder
{
    /**
     * Seed clinicas, distributed across the existing empresas.
     *
     * Idempotent: keyed by (empresa_id, nombre) so re-running is safe.
     */
    public function run(): void
    {
        $zumed = Empresa::where('nombre', 'Zumed Medicina Diagnóstica')->first();
        $rcmed = Empresa::where('nombre', 'RCMed')->first();

        if (! $zumed) {
            $this->command->error('Empresa "Zumed Medicina Diagnóstica" no encontrada. Ejecutá EmpresaSeeder primero.');

            return;
        }

        $clinicas = [
            // Zumed — 4 clínicas
            ['empresa_id' => $zumed->id, 'nombre' => 'Clínica San José',          'direccion' => 'Av. Principal 123', 'telefono' => '555-0001'],
            ['empresa_id' => $zumed->id, 'nombre' => 'Centro Médico Santa Rosa',  'direccion' => 'Jr. Los Olivos 456', 'telefono' => '555-0002'],
            ['empresa_id' => $zumed->id, 'nombre' => 'Clínica del Norte',         'direccion' => 'Calle Norte 321',   'telefono' => '555-0003'],
            ['empresa_id' => $zumed->id, 'nombre' => 'Centro de Diagnóstico Integral', 'direccion' => 'Av. Diagnóstico 654', 'telefono' => '555-0004'],
            // RCMed — 2 clínicas (solo si la empresa existe)
        ];

        if ($rcmed) {
            $clinicas[] = ['empresa_id' => $rcmed->id, 'nombre' => 'RCMed Central',  'direccion' => 'Av. Corrientes 1000', 'telefono' => '555-0101'];
            $clinicas[] = ['empresa_id' => $rcmed->id, 'nombre' => 'RCMed Sucursal', 'direccion' => 'Av. Belgrano 2000',  'telefono' => '555-0102'];
        }

        $created = 0;
        foreach ($clinicas as $data) {
            $clinica = Clinica::firstOrCreate(
                ['empresa_id' => $data['empresa_id'], 'nombre' => $data['nombre']],
                $data,
            );
            if ($clinica->wasRecentlyCreated) {
                $created++;
            }
        }

        $this->command->info("{$created} clínicas nuevas creadas (total: " . Clinica::count() . ').');
    }
}
