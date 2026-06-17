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
        if (DB::table('users')->whereNull('empresa_id')->exists()) {
            throw new \RuntimeException(
                'Cannot make empresa_id NOT NULL: null records exist in users table. ' .
                'Run the Phase 1 data migration first to assign empresa_id to all records.'
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable(false)->change();
        });

        Schema::table('users', function (Blueprint $table) {
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
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['empresa_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('empresa_id')->nullable()->change();
        });

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('empresa_id')
                  ->references('id')
                  ->on('empresas')
                  ->nullOnDelete();
        });
    }
};
