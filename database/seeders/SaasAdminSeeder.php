<?php

namespace Database\Seeders;

use App\Models\SaasAdmin;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SaasAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SaasAdmin::create([
            'name' => 'Admin SaaS',
            'email' => 'admin@saas.com',
            'password' => Hash::make('password'),
        ]);
    }
}
