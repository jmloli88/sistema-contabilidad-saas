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
        Schema::create('repase_examenes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repase_id')->constrained('repases')->onDelete('cascade');
            $table->foreignId('examen_id')->constrained('examenes')->onDelete('restrict');
            $table->unsignedInteger('cantidad');
            $table->decimal('precio_unitario_usado', 10, 2);
            $table->decimal('subtotal', 10, 2);
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index('repase_id');
            $table->index('examen_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('repase_examenes');
    }
};
