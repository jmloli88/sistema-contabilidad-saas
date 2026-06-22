<?php

namespace Database\Seeders;

use App\Models\Empresa;
use Illuminate\Database\Seeder;

class EmpresaSeeder extends Seeder
{
    /**
     * Seed the empresas table with demo tenants.
     *
     * Idempotent: uses firstOrCreate so re-running never duplicates.
     * These names match the empresas already present in the dev database
     * so the seeder stays in sync with existing data instead of spawning
     * parallel "Default X Empresa" rows.
     */
    public function run(): void
    {
        $empresas = [
            ['nombre' => 'Zumed Medicina Diagnóstica'],
            ['nombre' => 'RCMed'],
        ];

        foreach ($empresas as $data) {
            Empresa::firstOrCreate(['nombre' => $data['nombre']], $data);
        }

        $this->command->info(sprintf(
            '%d empresas ensured (%s).',
            Empresa::count(),
            Empresa::pluck('nombre')->implode(', '),
        ));
    }
}
