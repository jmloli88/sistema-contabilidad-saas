<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates a seed empresa "Zumed Medicina Diagnóstica" (slug: "zumeddg")
     * and assigns all existing records in clinicas, users, examenes, and
     * subscriptions to it. Idempotent — checks for existing seed empresa
     * before inserting, and only updates records where empresa_id IS NULL.
     */
    public function up(): void
    {
        // 1. Create or retrieve the seed empresa (idempotent)
        $empresaId = DB::table('empresas')->where('nombre', 'Zumed Medicina Diagnóstica')->value('id');

        if (! $empresaId) {
            $empresaId = DB::table('empresas')->insertGetId([
                'nombre' => 'Zumed Medicina Diagnóstica',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Assign existing records to the seed empresa
        // Only update where empresa_id IS NULL (idempotent)
        $tables = ['clinicas', 'users', 'examenes', 'subscriptions'];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'empresa_id')) {
                DB::table($table)
                    ->whereNull('empresa_id')
                    ->update(['empresa_id' => $empresaId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     *
     * Sets empresa_id back to NULL on all records that belong to the seed empresa.
     */
    public function down(): void
    {
        $empresaId = DB::table('empresas')->where('nombre', 'Zumed Medicina Diagnóstica')->value('id');

        if ($empresaId) {
            $tables = ['clinicas', 'users', 'examenes', 'subscriptions'];

            foreach ($tables as $table) {
                if (Schema::hasColumn($table, 'empresa_id')) {
                    DB::table($table)
                        ->where('empresa_id', $empresaId)
                        ->update(['empresa_id' => null]);
                }
            }

            DB::table('empresas')->where('id', $empresaId)->delete();
        }
    }
};
