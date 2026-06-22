<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds empresa_id (nullable + FK + index) to the core billing tables that
     * were missed by the initial multi-tenancy migration: repases, gastos,
     * repase_examenes, agendas. Nullable here so the seed migration can
     * backfill before we enforce NOT NULL in a follow-up migration.
     */
    public function up(): void
    {
        $tables = ['repases', 'gastos', 'repase_examenes', 'agendas'];

        foreach ($tables as $table) {
            if (! Schema::hasColumn($table, 'empresa_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->foreignId('empresa_id')
                        ->nullable()
                        ->after('id')
                        ->constrained('empresas')
                        ->cascadeOnUpdate()
                        ->restrictOnDelete();
                    $blueprint->index('empresa_id');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = ['repases', 'gastos', 'repase_examenes', 'agendas'];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'empresa_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->dropForeign(['empresa_id']);
                    $blueprint->dropIndex(['empresa_id']);
                    $blueprint->dropColumn('empresa_id');
                });
            }
        }
    }
};
