<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Fix any existing saas_admin users — reassign to administrador
        DB::table('users')->where('role', 'saas_admin')->update(['role' => 'administrador']);

        // Remove saas_admin from the role column constraint
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: recreate column without saas_admin
            DB::statement("ALTER TABLE users ADD COLUMN role_fixed TEXT NOT NULL DEFAULT 'usuario' CHECK (role_fixed IN ('usuario', 'administrador'))");
            DB::statement("UPDATE users SET role_fixed = role");
            DB::statement("ALTER TABLE users DROP COLUMN role");
            DB::statement("ALTER TABLE users RENAME COLUMN role_fixed TO role");
        } else {
            // MySQL: convert to string first, then modify ENUM
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('usuario')->change();
            });

            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('usuario', 'administrador') NOT NULL DEFAULT 'usuario'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Restore saas_admin to the ENUM if needed
        if (DB::getDriverName() === 'sqlite') {
            DB::statement("ALTER TABLE users ADD COLUMN role_old TEXT NOT NULL DEFAULT 'usuario' CHECK (role_old IN ('usuario', 'administrador', 'saas_admin'))");
            DB::statement("UPDATE users SET role_old = role");
            DB::statement("ALTER TABLE users DROP COLUMN role");
            DB::statement("ALTER TABLE users RENAME COLUMN role_old TO role");
        } else {
            Schema::table('users', function (Blueprint $table) {
                $table->string('role')->default('usuario')->change();
            });

            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('usuario', 'administrador', 'saas_admin') NOT NULL DEFAULT 'usuario'");
        }
    }
};
