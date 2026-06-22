<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Order matters: each seeder depends on the previous one.
     *   1. Empresas        — tenant root
     *   2. Exámenes        — per-empresa catalog (needs Empresa)
     *   3. Admin user      — needs Empresa to attach to
     *   4. SaaS admin      — separate auth guard, no tenant
     *   5. Clínicas        — needs Empresa
     *   6. Repases         — needs Clínicas + Exámenes (creates gastos + repase_examenes)
     *   7. Agendas         — needs Clínicas
     */
    public function run(): void
    {
        $this->call([
            EmpresaSeeder::class,
            ExamenSeeder::class,
            AdminUserSeeder::class,
            SaasAdminSeeder::class,
            ClinicaSeeder::class,
            RepaseSeeder::class,
            AgendaSeeder::class,
        ]);
    }
}
