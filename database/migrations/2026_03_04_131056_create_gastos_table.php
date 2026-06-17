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
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('repase_id')->constrained('repases')->onDelete('cascade');
            $table->enum('tipo', ['doctor', 'tecnico', 'laudos', 'gasolina', 'extra']);
            $table->string('descripcion', 255)->nullable();
            $table->decimal('monto', 10, 2);
            $table->timestamps();

            // Índices
            $table->index('repase_id');
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};
