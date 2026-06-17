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
        // Safety check: ensure no null empresa_id records exist
        if (DB::table('clinicas')->whereNull('empresa_id')->exists()) {
            throw new \RuntimeException(
                'Cannot make empresa_id NOT NULL: null records exist in clinicas table. ' .
                'Run the Phase 1 data migration first to assign empresa_id to all records.'
            );
        }

        // The existing FK uses nullOnDelete() which prevents NOT NULL in MySQL.
        // Drop it, make the column NOT NULL, re-add with cascadeOnDelete()
        // for proper tenant data integrity.
        Schema::table('clinicas', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
        });

        Schema::table('clinicas', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable(false)->change();
        });

        Schema::table('clinicas', function (Blueprint $table) {
            $table->foreign('empresa_id')
                  ->references('id')
                  ->on('empresas')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clinicas', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
        });

        Schema::table('clinicas', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->change();
        });

        Schema::table('clinicas', function (Blueprint $table) {
            $table->foreign('empresa_id')
                  ->references('id')
                  ->on('empresas')
                  ->nullOnDelete();
        });
    }
};
