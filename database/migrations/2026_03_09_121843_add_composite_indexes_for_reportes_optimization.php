<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Agrega índices compuestos para optimizar las consultas de reportes avanzados.
     * Estos índices mejoran el rendimiento de filtros por fecha y clínica, y joins
     * con tablas relacionadas.
     */
    public function up(): void
    {
        Schema::table('repases', function (Blueprint $table) {
            // Índice compuesto para filtros de fecha y clínica en reportes
            // Optimiza consultas que filtran por rango de fechas y clínica específica
            $table->index(['fecha', 'clinica_id'], 'idx_repases_fecha_clinica');
        });

        Schema::table('repase_examenes', function (Blueprint $table) {
            // Índice compuesto para joins y filtros por repase y examen
            // Optimiza consultas de rentabilidad por tipo de examen
            $table->index(['repase_id', 'examen_id'], 'idx_repase_examenes_repase_examen');
        });

        // Nota: El índice en gastos(repase_id) ya existe en la migración original
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repases', function (Blueprint $table) {
            $table->dropIndex('idx_repases_fecha_clinica');
        });

        Schema::table('repase_examenes', function (Blueprint $table) {
            $table->dropIndex('idx_repase_examenes_repase_examen');
        });
    }
};
