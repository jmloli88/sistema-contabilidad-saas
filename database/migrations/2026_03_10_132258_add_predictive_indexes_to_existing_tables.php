<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Índices adicionales específicos para consultas predictivas
        Schema::table('repases', function (Blueprint $table) {
            // Índice que incluye total_neto para optimizar cálculos de ingresos
            $table->index(['fecha', 'clinica_id', 'total_neto'], 'idx_repases_predictive_income');
            // Índice para agrupaciones mensuales
            $table->index('fecha', 'idx_repases_fecha_monthly');
        });

        // Índices para tabla gastos - optimizar análisis por tipo
        Schema::table('gastos', function (Blueprint $table) {
            $table->index(['tipo', 'monto'], 'idx_gastos_tipo_monto');
        });

        // Índices para tabla repase_examenes - incluir subtotal para cálculos
        Schema::table('repase_examenes', function (Blueprint $table) {
            $table->index(['repase_id', 'subtotal'], 'idx_repase_examenes_subtotal');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repases', function (Blueprint $table) {
            $table->dropIndex('idx_repases_predictive_income');
            $table->dropIndex('idx_repases_fecha_monthly');
        });

        Schema::table('gastos', function (Blueprint $table) {
            $table->dropIndex('idx_gastos_tipo_monto');
        });

        Schema::table('repase_examenes', function (Blueprint $table) {
            $table->dropIndex('idx_repase_examenes_subtotal');
        });
    }
};
