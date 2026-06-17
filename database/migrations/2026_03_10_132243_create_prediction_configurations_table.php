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
        Schema::create('prediction_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value');
            $table->text('description')->nullable();
            $table->text('validation_rules')->nullable();
            $table->timestamps();
        });

        // Insertar configuraciones por defecto
        DB::table('prediction_configurations')->insert([
            [
                'key' => 'expense_alert_threshold',
                'value' => '25',
                'description' => 'Umbral de alerta para gastos (% sobre promedio)',
                'validation_rules' => 'numeric|min:1|max:50',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'active_algorithms',
                'value' => '["linear_regression","moving_average","seasonal"]',
                'description' => 'Algoritmos activos para predicción',
                'validation_rules' => 'json',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'cache_duration_minutes',
                'value' => '60',
                'description' => 'Duración del caché en minutos',
                'validation_rules' => 'numeric|min:5|max:1440',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'min_historical_months',
                'value' => '12',
                'description' => 'Mínimo de meses históricos requeridos',
                'validation_rules' => 'numeric|min:6|max:60',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'key' => 'capacity_alert_threshold',
                'value' => '85',
                'description' => 'Umbral de alerta de capacidad (%)',
                'validation_rules' => 'numeric|min:50|max:95',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prediction_configurations');
    }
};
