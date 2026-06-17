<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('repases', function (Blueprint $table) {
            $table->text('comentarios_operativos')->nullable()->after('observaciones');
            $table->text('comentarios_administrativos')->nullable()->after('comentarios_operativos');
            $table->text('comentarios_caja_chica')->nullable()->after('comentarios_administrativos');
            $table->text('comentarios_insumios_medicos')->nullable()->after('comentarios_caja_chica');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('repases', function (Blueprint $table) {
            $table->dropColumn([
                'comentarios_operativos',
                'comentarios_administrativos',
                'comentarios_caja_chica',
                'comentarios_insumios_medicos'
            ]);
        });
    }
};
