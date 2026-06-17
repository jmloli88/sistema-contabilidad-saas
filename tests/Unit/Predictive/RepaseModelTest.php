<?php

namespace Tests\Unit\Predictive;

use Tests\TestCase;
use App\Models\Repase;
use App\Models\Clinica;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RepaseModelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Crear datos de prueba
        $clinica = Clinica::create([
            'nombre' => 'Clínica Test',
            'direccion' => 'Dirección Test',
            'telefono' => '123456789'
        ]);

        Repase::create([
            'clinica_id' => $clinica->id,
            'fecha' => '2024-01-15',
            'estado' => 'pagado',
            'tipo_precio' => 'con_nota',
            'total_examenes' => 1000.00,
            'total_consultas' => 500.00,
            'total_gastos' => 200.00,
            'total_neto' => 1300.00,
        ]);

        Repase::create([
            'clinica_id' => $clinica->id,
            'fecha' => '2024-02-15',
            'estado' => 'pagado',
            'tipo_precio' => 'con_nota',
            'total_examenes' => 1200.00,
            'total_consultas' => 600.00,
            'total_gastos' => 250.00,
            'total_neto' => 1550.00,
        ]);
    }

    /**
     * Test del scope forPrediction con filtros comprehensivos
     */
    public function test_for_prediction_scope_with_comprehensive_filters()
    {
        $clinica = Clinica::first();
        
        // Test con todos los filtros
        $filters = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'clinica_id' => $clinica->id,
            'estado' => 'pagado',
            'tipo_precio' => 'con_nota',
            'min_total' => 1000.00,
            'max_total' => 2000.00
        ];

        $repases = Repase::forPrediction($filters)->get();
        
        $this->assertCount(1, $repases);
        $this->assertEquals('2024-01-15', $repases->first()->fecha->format('Y-m-d'));
        $this->assertEquals('pagado', $repases->first()->estado);
        $this->assertEquals('con_nota', $repases->first()->tipo_precio);
        $this->assertGreaterThanOrEqual(1000.00, $repases->first()->total_neto);
        $this->assertLessThanOrEqual(2000.00, $repases->first()->total_neto);
    }

    /**
     * Test del scope forPrediction sin filtros
     */
    public function test_for_prediction_scope_without_filters()
    {
        $repases = Repase::forPrediction()->get();
        
        $this->assertCount(2, $repases);
        // Verificar que están ordenados por fecha
        $this->assertEquals('2024-01-15', $repases->first()->fecha->format('Y-m-d'));
        $this->assertEquals('2024-02-15', $repases->last()->fecha->format('Y-m-d'));
    }

    /**
     * Test del scope groupedByMonth
     */
    public function test_grouped_by_month_scope_works()
    {
        $grouped = Repase::groupedByMonth()->get();
        
        $this->assertCount(2, $grouped);
        
        // Verificar que devuelve modelos con los campos agregados
        $firstGroup = $grouped->first();
        $this->assertNotNull($firstGroup->month);
        $this->assertNotNull($firstGroup->total_repases);
        $this->assertNotNull($firstGroup->total_ingresos);
        $this->assertNotNull($firstGroup->clinica_id);
        
        // Verificar valores específicos
        $this->assertEquals('2024-01', $firstGroup->month);
        $this->assertEquals(1, $firstGroup->total_repases);
        $this->assertEquals('1300.00', $firstGroup->total_ingresos);
    }

    /**
     * Test del scope groupedByMonth con opciones avanzadas
     */
    public function test_grouped_by_month_scope_with_options()
    {
        // Test con opciones básicas
        $options = [
            'group_by_clinica' => true
        ];

        $grouped = Repase::groupedByMonth($options)->get();
        
        $this->assertCount(2, $grouped);
        
        // Verificar que incluye campos adicionales
        $firstGroup = $grouped->first();
        $this->assertNotNull($firstGroup->month);
        $this->assertNotNull($firstGroup->total_repases);
        $this->assertNotNull($firstGroup->total_ingresos);
        $this->assertNotNull($firstGroup->clinica_id);
        $this->assertNotNull($firstGroup->promedio_ingresos);
        $this->assertNotNull($firstGroup->min_ingresos);
        $this->assertNotNull($firstGroup->max_ingresos);
    }

    /**
     * Test del scope groupedByMonth con gastos incluidos
     */
    public function test_grouped_by_month_scope_with_gastos()
    {
        // Crear un gasto para probar
        $repase = Repase::first();
        \App\Models\Gasto::create([
            'repase_id' => $repase->id,
            'tipo' => 'doctor',
            'descripcion' => 'Gasto test',
            'monto' => 200.00
        ]);

        // Test con opciones de gastos
        $options = [
            'include_gastos' => true,
            'group_by_clinica' => true
        ];

        $grouped = Repase::groupedByMonth($options)->get();
        
        $this->assertGreaterThan(0, $grouped->count());
        
        // Verificar que incluye campos de gastos
        $firstGroup = $grouped->first();
        $this->assertNotNull($firstGroup->gastos_doctor);
        $this->assertNotNull($firstGroup->gastos_tecnico);
        $this->assertNotNull($firstGroup->gastos_laudos);
        $this->assertNotNull($firstGroup->gastos_gasolina);
        $this->assertNotNull($firstGroup->gastos_extra);
    }

    /**
     * Test del scope forCapacityAnalysis
     */
    public function test_for_capacity_analysis_scope()
    {
        // Crear examen primero para satisfacer la foreign key
        $examen = \App\Models\Examen::create([
            'nombre' => 'Examen Test',
            'precio_sin_nota' => 400.00,
            'precio_con_nota' => 500.00
        ]);

        // Crear datos de prueba con exámenes
        $repase = Repase::first();
        \App\Models\RepaseExamen::create([
            'repase_id' => $repase->id,
            'examen_id' => $examen->id,
            'cantidad' => 2,
            'precio_unitario_usado' => 500.00,
            'subtotal' => 1000.00
        ]);

        $filters = ['clinica_id' => $repase->clinica_id];
        $results = Repase::forCapacityAnalysis($filters)->get();
        
        $this->assertGreaterThan(0, $results->count());
        
        $firstResult = $results->first();
        $this->assertNotNull($firstResult->total_examenes_count);
        $this->assertNotNull($firstResult->total_examenes_value);
        $this->assertEquals($repase->clinica_id, $firstResult->clinica_id);
    }

    /**
     * Test del scope forCorrelationAnalysis
     */
    public function test_for_correlation_analysis_scope()
    {
        // Crear datos de prueba con gastos
        $repase = Repase::first();
        \App\Models\Gasto::create([
            'repase_id' => $repase->id,
            'tipo' => 'doctor',
            'descripcion' => 'Gasto test',
            'monto' => 200.00
        ]);

        $filters = ['clinica_id' => $repase->clinica_id];
        $results = Repase::forCorrelationAnalysis($filters)->get();
        
        $this->assertGreaterThan(0, $results->count());
        
        $firstResult = $results->first();
        $this->assertNotNull($firstResult->ingresos);
        $this->assertNotNull($firstResult->gastos_totales);
        $this->assertNotNull($firstResult->gastos_count);
        $this->assertEquals($repase->clinica_id, $firstResult->clinica_id);
    }

    /**
     * Test del scope groupedByPeriod
     */
    public function test_grouped_by_period_scope()
    {
        // Test agrupación por mes
        $results = Repase::groupedByPeriod('month')->get();
        $this->assertGreaterThan(0, $results->count());
        
        $firstResult = $results->first();
        $this->assertNotNull($firstResult->period);
        $this->assertNotNull($firstResult->total_repases);
        $this->assertNotNull($firstResult->total_ingresos);
        $this->assertNotNull($firstResult->promedio_ingresos);
        
        // Test agrupación por año
        $results = Repase::groupedByPeriod('year')->get();
        $this->assertGreaterThan(0, $results->count());
    }

    /**
     * Test del scope forSeasonalAnalysis
     */
    public function test_for_seasonal_analysis_scope()
    {
        $results = Repase::forSeasonalAnalysis()->get();
        
        $this->assertGreaterThan(0, $results->count());
        
        $firstResult = $results->first();
        $this->assertNotNull($firstResult->mes);
        $this->assertNotNull($firstResult->año);
        $this->assertNotNull($firstResult->dia_del_año);
        $this->assertNotNull($firstResult->valor);
        $this->assertNotNull($firstResult->secuencia);
    }

    /**
     * Test del scope statisticalSummary
     */
    public function test_statistical_summary_scope()
    {
        $results = Repase::statisticalSummary()->get();
        
        $this->assertGreaterThan(0, $results->count());
        
        $firstResult = $results->first();
        $this->assertNotNull($firstResult->total_registros);
        $this->assertNotNull($firstResult->suma_ingresos);
        $this->assertNotNull($firstResult->media_ingresos);
        $this->assertNotNull($firstResult->min_ingresos);
        $this->assertNotNull($firstResult->max_ingresos);
        $this->assertNotNull($firstResult->fecha_inicio);
        $this->assertNotNull($firstResult->fecha_fin);
    }

    /**
     * Test de métodos helper
     */
    public function test_helper_methods()
    {
        $repase = Repase::first();
        
        // Test getTotalCalculadoAttribute
        $totalCalculado = $repase->total_calculado;
        $expectedTotal = $repase->total_examenes + $repase->total_consultas - $repase->total_gastos;
        $this->assertEquals($expectedTotal, $totalCalculado);
        
        // Test hasValidDataForPrediction
        $this->assertTrue($repase->hasValidDataForPrediction());
        
        // Test getMonthYearAttribute
        $monthYear = $repase->month_year;
        $this->assertEquals($repase->fecha->format('Y-m'), $monthYear);
    }

    /**
     * Test del scope forBulkAnalysis
     */
    public function test_for_bulk_analysis_scope()
    {
        $results = Repase::forBulkAnalysis()->get();
        
        $this->assertGreaterThan(0, $results->count());
        
        // Verificar que solo incluye campos necesarios para optimización
        $firstResult = $results->first();
        $this->assertNotNull($firstResult->id);
        $this->assertNotNull($firstResult->clinica_id);
        $this->assertNotNull($firstResult->fecha);
        $this->assertNotNull($firstResult->total_neto);
        $this->assertNotNull($firstResult->estado);
    }

    /**
     * Test del scope withValidData
     */
    public function test_with_valid_data_scope()
    {
        $results = Repase::withValidData()->get();
        
        $this->assertGreaterThan(0, $results->count());
        
        // Verificar que todos los registros tienen datos válidos
        foreach ($results as $repase) {
            $this->assertGreaterThan(0, $repase->total_neto);
            $this->assertNotNull($repase->fecha);
            $this->assertNotNull($repase->clinica_id);
            $this->assertLessThanOrEqual(now(), $repase->fecha);
        }
    }

    /**
     * Test que los scopes existentes sigan funcionando
     */
    public function test_existing_scopes_still_work()
    {
        $clinica = Clinica::first();
        
        $repases = Repase::byClinica($clinica->id)->get();
        $this->assertCount(2, $repases);

        $repases = Repase::byEstado('pagado')->get();
        $this->assertCount(2, $repases);

        $repases = Repase::byDateRange('2024-01-01', '2024-01-31')->get();
        $this->assertCount(1, $repases);
    }
}