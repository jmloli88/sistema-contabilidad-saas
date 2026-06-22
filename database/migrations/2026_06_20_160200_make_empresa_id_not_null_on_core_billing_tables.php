<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Enforces empresa_id NOT NULL on the core billing tables after the seed
     * migration backfilled existing rows. Aborts (with a clear message) if any
     * row is still NULL — that would indicate orphaned records that must be
     * fixed manually before re-running.
     */
    public function up(): void
    {
        $tables = ['repases', 'gastos', 'repase_examenes', 'agendas'];

        foreach ($tables as $table) {
            if (! Schema::hasColumn($table, 'empresa_id')) {
                continue;
            }

            $nullCount = DB::table($table)->whereNull('empresa_id')->count();
            if ($nullCount > 0) {
                throw new RuntimeException(
                    "Cannot make {$table}.empresa_id NOT NULL: {$nullCount} rows still have NULL. "
                    . 'Fix orphaned records manually before re-running this migration.'
                );
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->unsignedBigInteger('empresa_id')->nullable(false)->change();
            });
        }
    }

    public function down(): void
    {
        $tables = ['repases', 'gastos', 'repase_examenes', 'agendas'];

        foreach ($tables as $table) {
            if (Schema::hasColumn($table, 'empresa_id')) {
                Schema::table($table, function (Blueprint $blueprint) {
                    $blueprint->unsignedBigInteger('empresa_id')->nullable()->change();
                });
            }
        }
    }
};
