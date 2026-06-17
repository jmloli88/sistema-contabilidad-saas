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
        Schema::create('prediction_accuracy_log', function (Blueprint $table) {
            $table->id();
            $table->string('prediction_type', 100);
            $table->string('algorithm', 100);
            $table->date('prediction_date');
            $table->date('actual_date');
            $table->decimal('predicted_value', 15, 2);
            $table->decimal('actual_value', 15, 2);
            $table->decimal('absolute_error', 15, 2);
            $table->decimal('percentage_error', 8, 4);
            $table->timestamp('created_at');

            // Índices para optimizar consultas de métricas
            $table->index(['prediction_type', 'algorithm'], 'idx_accuracy_log_type_algorithm');
            $table->index(['prediction_date', 'actual_date'], 'idx_accuracy_log_dates');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediction_accuracy_log');
    }
};
