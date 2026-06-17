<?php

namespace Tests\Unit\Predictive;

use App\Services\Predictive\CapacityAnalyzer;
use App\DTOs\Predictive\CapacityAnalysis;
use App\Models\Repase;
use App\Models\Clinica;
use App\Models\RepaseExamen;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CapacityAnalyzerTest extends TestCase
{
    use RefreshDatabase;

    private CapacityAnalyzer $capacityAnalyzer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->capacityAnalyzer = new CapacityAnalyzer();
    }

    public function test_analyze_current_capacity_returns_capacity_analysis()
    {
        // Crear datos de prueba
        $clinic = Clinica::factory()->create(['nombre' => 'Clínica Test']);
        $this->createTestRepasesWithExams($clinic->id, 5, 100); // 5 repases con 100 exámenes cada uno

        $filters = ['clinica_id' => $clinic->id];
        $result = $this->capacityAnalyzer->analyzeCurrentCapacity($filters);

        $this->assertInstanceOf(CapacityAnalysis::class, $result);
        $this->assertIsFloat($result->currentUtilization);
        $this->assertIsArray($result->clinicUtilization);
        $this->assertIsArray($result->bottlenecks);
        $this->assertIsArray($result->recommendations);
        $this->assertArrayHasKey('filters', $result->metadata);
    }

    public function test_capacity_utilization_calculation_is_correct()
    {
        $clinic = Clinica::factory()->create(['nombre' => 'Clínica Test']);
        
        // Crear 500 exámenes en el mes actual (50% de capacidad por defecto de 1000)
        $this->createCurrentMonthExams($clinic->id, 500);

        $filters = ['clinica_id' => $clinic->id];
        $result = $this->capacityAnalyzer->analyzeCurrentCapacity($filters);

        // Verificar que la utilización sea aproximadamente 50%
        $this->assertEqualsWithDelta(50.0, $result->currentUtilization, 1.0);
        
        // Verificar datos de la clínica específica
        $this->assertCount(1, $result->clinicUtilization);
        $clinicData = $result->clinicUtilization[0];
        $this->assertEquals($clinic->id, $clinicData['clinic_id']);
        $this->assertEquals(500, $clinicData['current_exams']);
        $this->assertEquals(1000, $clinicData['max_capacity']);
        $this->assertEqualsWithDelta(50.0, $clinicData['utilization_percentage'], 1.0);
    }

    public function test_high_utilization_generates_alerts()
    {
        $clinic = Clinica::factory()->create(['nombre' => 'Clínica Saturada']);
        
        // Crear 900 exámenes (90% de capacidad - por encima del umbral de 85%)
        $this->createCurrentMonthExams($clinic->id, 900);

        $filters = ['clinica_id' => $clinic->id];
        $result = $this->capacityAnalyzer->analyzeCurrentCapacity($filters);

        // Verificar que se detecten cuellos de botella
        $this->assertNotEmpty($result->bottlenecks);
        
        // Verificar que se generen recomendaciones
        $this->assertNotEmpty($result->recommendations);
        
        // Verificar estado crítico
        $clinicData = $result->clinicUtilization[0];
        $this->assertEquals('critical', $clinicData['status']);
    }

    public function test_project_saturation_date_with_growth_trend()
    {
        $clinic = Clinica::factory()->create();
        
        // Crear datos históricos con tendencia creciente
        $this->createHistoricalDataWithGrowth($clinic->id);

        $filters = ['clinica_id' => $clinic->id];
        $saturationDate = $this->capacityAnalyzer->projectSaturationDate($filters);

        $this->assertInstanceOf(Carbon::class, $saturationDate);
        $this->assertGreaterThan(Carbon::now(), $saturationDate);
    }

    public function test_project_saturation_date_returns_null_with_insufficient_data()
    {
        $clinic = Clinica::factory()->create();
        
        // Crear solo 3 meses de datos (menos del mínimo de 6)
        $this->createTestRepasesWithExams($clinic->id, 3, 50);

        $filters = ['clinica_id' => $clinic->id];
        $saturationDate = $this->capacityAnalyzer->projectSaturationDate($filters);

        $this->assertNull($saturationDate);
    }

    public function test_recommend_actions_returns_appropriate_recommendations()
    {
        $clinic = Clinica::factory()->create(['nombre' => 'Clínica Test']);
        $this->createCurrentMonthExams($clinic->id, 900); // Alta utilización

        $filters = ['clinica_id' => $clinic->id];
        $analysis = $this->capacityAnalyzer->analyzeCurrentCapacity($filters);
        
        $recommendations = $this->capacityAnalyzer->recommendActions($analysis);

        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);
        
        // Verificar que las recomendaciones tengan la estructura correcta
        foreach ($recommendations as $recommendation) {
            $this->assertArrayHasKey('type', $recommendation);
            $this->assertArrayHasKey('priority', $recommendation);
            $this->assertArrayHasKey('title', $recommendation);
            $this->assertArrayHasKey('description', $recommendation);
            $this->assertArrayHasKey('actions', $recommendation);
        }
    }

    public function test_multiple_clinics_analysis()
    {
        $clinic1 = Clinica::factory()->create(['nombre' => 'Clínica 1']);
        $clinic2 = Clinica::factory()->create(['nombre' => 'Clínica 2']);
        
        $this->createCurrentMonthExams($clinic1->id, 300);
        $this->createCurrentMonthExams($clinic2->id, 700);

        $filters = []; // Sin filtro de clínica específica
        $result = $this->capacityAnalyzer->analyzeCurrentCapacity($filters);

        // Verificar que se analicen ambas clínicas
        $this->assertCount(2, $result->clinicUtilization);
        
        // Verificar utilización general (promedio ponderado)
        $expectedOverallUtilization = ((300 + 700) / (1000 + 1000)) * 100; // 50%
        $this->assertEqualsWithDelta($expectedOverallUtilization, $result->currentUtilization, 1.0);
    }

    public function test_bottleneck_detection_for_high_growth()
    {
        $clinic = Clinica::factory()->create(['nombre' => 'Clínica Crecimiento']);
        
        // Crear datos con crecimiento acelerado
        $this->createHistoricalDataWithHighGrowth($clinic->id);

        $filters = ['clinica_id' => $clinic->id];
        $result = $this->capacityAnalyzer->analyzeCurrentCapacity($filters);

        // Verificar que se detecte cuello de botella por crecimiento
        $growthBottlenecks = array_filter($result->bottlenecks, 
            fn($b) => $b['type'] === 'growth_bottleneck'
        );
        
        $this->assertNotEmpty($growthBottlenecks);
    }

    private function createTestRepasesWithExams(int $clinicId, int $months, int $examsPerMonth): void
    {
        $examen = \App\Models\Examen::factory()->create(); // Create one exam type to reuse
        
        for ($i = 0; $i < $months; $i++) {
            $date = Carbon::now()->subMonths($i);
            
            for ($j = 0; $j < $examsPerMonth; $j++) {
                $repase = Repase::factory()->create([
                    'clinica_id' => $clinicId,
                    'fecha' => $date->copy()->addDays(rand(0, 28))
                ]);
                
                RepaseExamen::factory()->create([
                    'repase_id' => $repase->id,
                    'examen_id' => $examen->id
                ]);
            }
        }
    }

    private function createCurrentMonthExams(int $clinicId, int $examCount): void
    {
        $examen = \App\Models\Examen::factory()->create(); // Create one exam type to reuse
        $daysInMonth = Carbon::now()->daysInMonth;
        $examsPerDay = ceil($examCount / $daysInMonth);
        
        $totalCreated = 0;
        for ($day = 1; $day <= $daysInMonth && $totalCreated < $examCount; $day++) {
            $date = Carbon::now()->startOfMonth()->addDays($day - 1);
            
            $examsToCreateToday = min($examsPerDay, $examCount - $totalCreated);
            
            for ($i = 0; $i < $examsToCreateToday; $i++) {
                $repase = Repase::factory()->create([
                    'clinica_id' => $clinicId,
                    'fecha' => $date->format('Y-m-d')
                ]);
                
                RepaseExamen::factory()->create([
                    'repase_id' => $repase->id,
                    'examen_id' => $examen->id
                ]);
                
                $totalCreated++;
            }
        }
    }

    private function createHistoricalDataWithGrowth(int $clinicId): void
    {
        $examen = \App\Models\Examen::factory()->create(); // Create one exam type to reuse
        
        // Crear 12 meses de datos con crecimiento gradual, incluyendo el mes actual
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $examCount = 100 + ((11 - $i) * 10); // Crecimiento de 10 exámenes por mes
            
            // Crear exámenes distribuidos a lo largo del mes
            $daysInMonth = $date->daysInMonth;
            $examsPerDay = ceil($examCount / $daysInMonth);
            
            $totalCreated = 0;
            for ($day = 1; $day <= $daysInMonth && $totalCreated < $examCount; $day++) {
                $examDate = $date->copy()->startOfMonth()->addDays($day - 1);
                
                $examsToCreateToday = min($examsPerDay, $examCount - $totalCreated);
                
                for ($j = 0; $j < $examsToCreateToday; $j++) {
                    $repase = Repase::factory()->create([
                        'clinica_id' => $clinicId,
                        'fecha' => $examDate->format('Y-m-d')
                    ]);
                    
                    RepaseExamen::factory()->create([
                        'repase_id' => $repase->id,
                        'examen_id' => $examen->id
                    ]);
                    
                    $totalCreated++;
                }
            }
        }
    }

    private function createHistoricalDataWithHighGrowth(int $clinicId): void
    {
        $examen = \App\Models\Examen::factory()->create(); // Create one exam type to reuse
        
        // Crear datos con crecimiento acelerado (>20% mensual)
        for ($i = 11; $i >= 0; $i--) {
            $date = Carbon::now()->subMonths($i);
            $examCount = 100 * pow(1.25, 11 - $i); // 25% crecimiento mensual
            
            for ($j = 0; $j < (int)$examCount; $j++) {
                $repase = Repase::factory()->create([
                    'clinica_id' => $clinicId,
                    'fecha' => $date->copy()->addDays(rand(0, 28))
                ]);
                
                RepaseExamen::factory()->create([
                    'repase_id' => $repase->id,
                    'examen_id' => $examen->id
                ]);
            }
        }
    }
}