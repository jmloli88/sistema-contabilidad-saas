<?php

namespace Database\Seeders;

use App\Models\Clinica;
use App\Models\Empresa;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $empresa = Empresa::firstOrCreate(['nombre' => 'Default Admin Empresa']);

        // Assign the admin user to the first clinic if clinics exist
        $firstClinica = Clinica::first();

        User::create([
            'name' => 'Administrador',
            'email' => 'admin@sistema.com',
            'password' => Hash::make('password'),
            'role' => 'administrador',
            'empresa_id' => $empresa->id,
            'clinica_id' => $firstClinica?->id,
            'email_verified_at' => now(),
        ]);
    }
}
