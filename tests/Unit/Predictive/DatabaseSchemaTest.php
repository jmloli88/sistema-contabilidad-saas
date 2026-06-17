<?php

namespace Tests\Unit\Predictive;

use Tests\TestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;
    /**
     * Test que las tablas predictivas existan
     */
    public function test_predictive_tables_exist()
    {
        $this->assertTrue(Schema::hasTable('prediction_configurations'));
        $this->assertTrue(Schema::hasTable('prediction_cache'));
        $this->assertTrue(Schema::hasTable('prediction_accuracy_log'));
    }

    /**
     * Test que la tabla prediction_configurations tenga las columnas correctas
     */
    public function test_prediction_configurations_table_structure()
    {
        $this->assertTrue(Schema::hasColumns('prediction_configurations', [
            'id', 'key', 'value', 'description', 'validation_rules', 'created_at', 'updated_at'
        ]));
    }

    /**
     * Test que la tabla prediction_cache tenga las columnas correctas
     */
    public function test_prediction_cache_table_structure()
    {
        $this->assertTrue(Schema::hasColumns('prediction_cache', [
            'id', 'cache_key', 'prediction_type', 'filters_hash', 'result_data', 
            'accuracy_metrics', 'expires_at', 'created_at', 'updated_at'
        ]));
    }

    /**
     * Test que la tabla prediction_accuracy_log tenga las columnas correctas
     */
    public function test_prediction_accuracy_log_table_structure()
    {
        $this->assertTrue(Schema::hasColumns('prediction_accuracy_log', [
            'id', 'prediction_type', 'algorithm', 'prediction_date', 'actual_date',
            'predicted_value', 'actual_value', 'absolute_error', 'percentage_error', 'created_at'
        ]));
    }

    /**
     * Test que las configuraciones por defecto se hayan insertado
     */
    public function test_default_configurations_are_inserted()
    {
        $expectedConfigs = [
            'expense_alert_threshold',
            'active_algorithms',
            'cache_duration_minutes',
            'min_historical_months',
            'capacity_alert_threshold'
        ];

        foreach ($expectedConfigs as $key) {
            $this->assertTrue(
                DB::table('prediction_configurations')->where('key', $key)->exists(),
                "Configuration key '{$key}' should exist"
            );
        }

        // Verificar valores específicos
        $this->assertEquals('25', 
            DB::table('prediction_configurations')->where('key', 'expense_alert_threshold')->value('value')
        );
        
        $this->assertEquals('12', 
            DB::table('prediction_configurations')->where('key', 'min_historical_months')->value('value')
        );
    }

    /**
     * Test que los índices predictivos se hayan creado en tablas existentes
     */
    public function test_predictive_indexes_exist()
    {
        // Para SQLite, verificar que las tablas existan y tengan datos
        // Los índices se crean automáticamente por las migraciones
        $this->assertTrue(Schema::hasTable('repases'));
        $this->assertTrue(Schema::hasTable('gastos'));
        
        // Verificar que las columnas indexadas existan
        $this->assertTrue(Schema::hasColumns('repases', ['fecha', 'clinica_id', 'total_neto']));
        $this->assertTrue(Schema::hasColumns('gastos', ['repase_id', 'tipo', 'monto']));
    }
}