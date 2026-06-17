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
        Schema::create('clinica_examen', function (Blueprint $table) {
            $table->foreignId('clinica_id')->constrained('clinicas')->cascadeOnDelete();
            $table->foreignId('examen_id')->constrained('examenes')->cascadeOnDelete();
            $table->decimal('precio_sin_nota', 10, 2)->nullable();
            $table->decimal('precio_con_nota', 10, 2)->nullable();
            $table->timestamps();

            // Composite primary key
            $table->primary(['clinica_id', 'examen_id']);

            // Indexes for individual FK lookups
            $table->index('clinica_id');
            $table->index('examen_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('clinica_examen');
    }
};
