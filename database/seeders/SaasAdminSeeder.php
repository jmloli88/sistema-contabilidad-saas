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
        SaasAdmin::firstOrCreate(
            ['email' => 'admin@saas.com'],
            [
                'name' => 'Admin SaaS',
                'password' => Hash::make('password'),
            ],
        );
    }
}
