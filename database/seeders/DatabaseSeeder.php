<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Orden de ejecución de seeders
        $this->call([
            ExamenSeeder::class,              // 1. Crear los 7 exámenes predefinidos
            AdminUserSeeder::class,           // 2. Crear usuario administrador
            ClinicaSeeder::class,             // 3. Crear clínicas
            RepaseSeeder::class,              // 4. Crear 10 repases con exámenes y gastos
            SaasAdminSeeder::class,           // 5. Crear usuario admin SaaS (tabla separada)
        ]);
    }
}
