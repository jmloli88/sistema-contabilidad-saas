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
        Schema::create('repases', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->onDelete('restrict');
            $table->date('fecha');
            $table->date('fecha_pago')->nullable();
            $table->enum('estado', ['pendiente', 'pagado'])->default('pendiente');
            $table->enum('tipo_precio', ['sin_nota', 'con_nota']);
            $table->decimal('total_examenes', 10, 2)->default(0);
            $table->decimal('total_consultas', 10, 2)->default(0);
            $table->decimal('total_gastos', 10, 2)->default(0);
            $table->decimal('total_neto', 10, 2)->default(0);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices
            $table->index('clinica_id');
            $table->index('fecha');
            $table->index('estado');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repases');
    }
};
