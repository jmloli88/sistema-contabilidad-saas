<?php

namespace Tests\Unit\Predictive;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Gasto;
use App\Models\Clinica;
use App\Models\Examen;
use App\Models\Repase;
use App\Models\RepaseExamen;
use Carbon\Carbon;

class ModelExtensionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create test data
        $this->createTestData();
    }

    public function test_gasto_model_has_predictive_scopes()
    {
        // Test forPredictiveAnalysis scope
        $gastos = Gasto::forPredictiveAnalysis([
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-12-31'
        ])->get();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $gastos);
        
        // Test groupedByMonth scope
        $monthlyGastos = Gasto::groupedByMonth()->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $monthlyGastos);
        
        // Test helper methods
        $gasto = Gasto::first();
        if ($gasto) {
            $this->assertTrue($gasto->hasValidDataForPrediction());
            $this->assertNotNull($gasto->category);
        }
    }

    public function test_clinica_model_has_capacity_analysis_extensions()
    {
        // Test forCapacityAnalysis scope
        $clinicas = Clinica::forCapacityAnalysis([
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-12-31'
        ])->get();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $clinicas);
        
        // Test monthlyUtilization scope
        $utilization = Clinica::monthlyUtilization()->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $utilization);
        
        // Test helper methods
        $clinica = Clinica::first();
        if ($clinica) {
            $utilizationStats = $clinica->calculateCurrentUtilization();
            $this->assertArrayHasKey('clinic_id', $utilizationStats);
            $this->assertArrayHasKey('utilization_percentage', $utilizationStats);
            
            $growthTrend = $clinica->calculateGrowthTrend();
            $this->assertArrayHasKey('trend', $growthTrend);
            $this->assertArrayHasKey('direction', $growthTrend);
        }
    }

    public function test_examen_model_has_utilization_tracking_extensions()
    {
        // Test forUtilizationAnalysis scope
        $examenes = Examen::forUtilizationAnalysis([
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-12-31'
        ])->get();

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $examenes);
        
        // Test utilizationStats scope
        $stats = Examen::utilizationStats()->get();
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $stats);
        
        // Test helper methods
        $examen = Examen::first();
        if ($examen) {
            $utilizationStats = $examen->calculateUtilizationStats();
            $this->assertArrayHasKey('examen_id', $utilizationStats);
            $this->assertArrayHasKey('total_utilizaciones', $utilizationStats);
            
            $trend = $examen->calculateUtilizationTrend();
            $this->assertArrayHasKey('trend', $trend);
            $this->assertArrayHasKey('direction', $trend);
            
            $ranking = $examen->getPopularityRanking();
            $this->assertArrayHasKey('posicion_ranking', $ranking);
        }
    }

    public function test_model_extensions_work_with_filters()
    {
        $clinica = Clinica::first();
        
        if ($clinica) {
            // Test Gasto with clinica filter
            $gastos = Gasto::forPredictiveAnalysis(['clinica_id' => $clinica->id])->get();
            $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $gastos);
            
            // Test Examen with clinica filter
            $examenes = Examen::forUtilizationAnalysis(['clinica_id' => $clinica->id])->get();
            $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $examenes);
        }
        
        // Test date range filters
        $gastosByDate = Gasto::forPredictiveAnalysis([
            'fecha_inicio' => now()->subMonths(3)->format('Y-m-d'),
            'fecha_fin' => now()->format('Y-m-d')
        ])->get();
        
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $gastosByDate);
    }

    public function test_model_extensions_handle_empty_data_gracefully()
    {
        // Clear all data
        Gasto::truncate();
        RepaseExamen::truncate();
        Repase::truncate();
        
        // Test scopes with no data
        $gastos = Gasto::forPredictiveAnalysis()->get();
        $this->assertEmpty($gastos);
        
        $clinicas = Clinica::forCapacityAnalysis()->get();
        $this->assertNotEmpty($clinicas); // Clinicas should still exist
        
        $examenes = Examen::forUtilizationAnalysis()->get();
        $this->assertNotEmpty($examenes); // Examenes should still exist
        
        // Test helper methods with no data
        $clinica = Clinica::first();
        if ($clinica) {
            $utilization = $clinica->calculateCurrentUtilization();
            $this->assertEquals(0, $utilization['current_exams']);
        }
        
        $examen = Examen::first();
        if ($examen) {
            $stats = $examen->calculateUtilizationStats();
            $this->assertEquals(0, $stats['total_utilizaciones']);
        }
    }

    private function createTestData()
    {
        // Create clinicas
        $clinica1 = Clinica::create([
            'nombre' => 'Clínica Test 1',
            'direccion' => 'Dirección Test 1',
            'telefono' => '123456789'
        ]);

        $clinica2 = Clinica::create([
            'nombre' => 'Clínica Test 2',
            'direccion' => 'Dirección Test 2',
            'telefono' => '987654321'
        ]);

        // Create examenes
        $examen1 = Examen::create([
            'nombre' => 'Examen Test 1',
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 150.00
        ]);

        $examen2 = Examen::create([
            'nombre' => 'Examen Test 2',
            'precio_sin_nota' => 200.00,
            'precio_con_nota' => 250.00
        ]);

        // Create repases for the last 6 months
        for ($i = 0; $i < 6; $i++) {
            $fecha = Carbon::now()->subMonths($i)->format('Y-m-d');
            
            // Repase for clinica 1
            $repase1 = Repase::create([
                'clinica_id' => $clinica1->id,
                'fecha' => $fecha,
                'fecha_pago' => $fecha,
                'estado' => 'pagado',
                'tipo_precio' => 'con_nota',
                'total_examenes' => 500.00,
                'total_consultas' => 200.00,
                'total_gastos' => 150.00,
                'total_neto' => 550.00
            ]);

            // Repase for clinica 2
            $repase2 = Repase::create([
                'clinica_id' => $clinica2->id,
                'fecha' => $fecha,
                'fecha_pago' => $fecha,
                'estado' => 'pagado',
                'tipo_precio' => 'sin_nota',
                'total_examenes' => 300.00,
                'total_consultas' => 100.00,
                'total_gastos' => 80.00,
                'total_neto' => 320.00
            ]);

            // Create gastos
            Gasto::create([
                'repase_id' => $repase1->id,
                'tipo' => 'doctor',
                'descripcion' => 'Pago doctor',
                'monto' => 100.00
            ]);

            Gasto::create([
                'repase_id' => $repase1->id,
                'tipo' => 'gasolina',
                'descripcion' => 'Combustible',
                'monto' => 50.00
            ]);

            Gasto::create([
                'repase_id' => $repase2->id,
                'tipo' => 'tecnico',
                'descripcion' => 'Pago técnico',
                'monto' => 80.00
            ]);

            // Create repase_examenes
            RepaseExamen::create([
                'repase_id' => $repase1->id,
                'examen_id' => $examen1->id,
                'cantidad' => 1,
                'precio_unitario_usado' => 150.00,
                'subtotal' => 150.00
            ]);

            RepaseExamen::create([
                'repase_id' => $repase1->id,
                'examen_id' => $examen2->id,
                'cantidad' => 1,
                'precio_unitario_usado' => 250.00,
                'subtotal' => 250.00
            ]);

            RepaseExamen::create([
                'repase_id' => $repase2->id,
                'examen_id' => $examen1->id,
                'cantidad' => 1,
                'precio_unitario_usado' => 100.00,
                'subtotal' => 100.00
            ]);

            RepaseExamen::create([
                'repase_id' => $repase2->id,
                'examen_id' => $examen2->id,
                'cantidad' => 1,
                'precio_unitario_usado' => 200.00,
                'subtotal' => 200.00
            ]);
        }
    }
}