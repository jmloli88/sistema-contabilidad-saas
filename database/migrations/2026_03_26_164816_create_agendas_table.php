<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('agendas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('clinica_id')->constrained('clinicas')->onDelete('cascade');
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->string('doctor');
            $table->enum('tipo_repeticion', ['unica', 'repetitiva'])->default('unica');
            $table->integer('dias_repeticion')->nullable();
            $table->string('grupo_repeticion')->nullable(); // Para agrupar agendas repetitivas
            $table->timestamps();
            
            // Índices para mejorar rendimiento
            $table->index(['fecha', 'clinica_id']);
            $table->index('grupo_repeticion');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agendas');
    }
};
