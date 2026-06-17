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
        Schema::create('prediction_cache', function (Blueprint $table) {
            $table->id();
            $table->string('cache_key')->unique();
            $table->string('prediction_type', 100);
            $table->string('filters_hash');
            $table->text('result_data'); // JSON
            $table->text('accuracy_metrics')->nullable(); // JSON con MAPE, RMSE
            $table->timestamp('expires_at');
            $table->timestamps();

            // Índices para optimizar consultas
            $table->index(['prediction_type', 'filters_hash'], 'idx_prediction_cache_type_hash');
            $table->index('expires_at', 'idx_prediction_cache_expires');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediction_cache');
    }
};
