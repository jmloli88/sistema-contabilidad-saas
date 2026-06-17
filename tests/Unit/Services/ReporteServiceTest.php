<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Reportes\ReporteService;
use App\Models\Clinica;
use App\Models\Repase;
use App\Models\Examen;
use App\Models\RepaseExamen;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ReporteServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ReporteService $reporteService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->reporteService = new ReporteService();
    }

    /** @test */
    public function calcula_margen_ganancia_correctamente()
    {
        $margen = $this->reporteService->calcularMargenGanancia(1000, 600);
        
        $this->assertEquals(40.0, $margen);
    }

    /** @test */
    public function margen_ganancia_retorna_null_cuando_ingresos_es_cero()
    {
        $margen = $this->reporteService->calcularMargenGanancia(0, 100);
        
        $this->assertNull($margen);
    }

    /** @test */
    public function calcula_variacion_porcentual_correctamente()
    {
        $variacion = $this->reporteService->calcularVariacionPorcentual(150, 100);
        
        $this->assertEquals(50.0, $variacion);
    }

    /** @test */
    public function variacion_porcentual_retorna_null_cuando_valor_anterior_es_cero()
    {
        $variacion = $this->reporteService->calcularVariacionPorcentual(100, 0);
        
        $this->assertNull($variacion);
    }

    /** @test */
    public function calcula_rentabilidad_clinica_con_datos_basicos()
    {
        $fecha = now()->startOfDay();
        $clinica = Clinica::factory()->create(['nombre' => 'Clínica Test']);
        
        Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => $fecha,
            'total_examenes' => 1000,
            'total_consultas' => 500,
            'total_gastos' => 600,
            'total_neto' => 900,
        ]);

        $resultado = $this->reporteService->calcularRentabilidadClinica([
            'fecha_inicio' => $fecha->format('Y-m-d'),
            'fecha_fin' => $fecha->format('Y-m-d'),
        ]);

        $this->assertGreaterThan(0, $resultado->count());
        $clinicaResult = $resultado->firstWhere('clinica_id', $clinica->id);
        $this->assertNotNull($clinicaResult);
        $this->assertEquals('Clínica Test', $clinicaResult->nombre_clinica);
        $this->assertEquals(1500, $clinicaResult->total_ingresos);
        $this->assertEquals(600, $clinicaResult->total_gastos);
        $this->assertEquals(900, $clinicaResult->ganancia_neta);
        $this->assertEquals(60.0, $clinicaResult->margen_ganancia);
    }

    /** @test */
    public function calcula_rentabilidad_examen_con_datos_basicos()
    {
        $fecha = now()->startOfDay();
        $clinica = Clinica::factory()->create();
        $examen = Examen::factory()->create(['nombre' => 'Examen Test']);
        
        $repase = Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => $fecha,
        ]);

        RepaseExamen::factory()->create([
            'repase_id' => $repase->id,
            'examen_id' => $examen->id,
            'cantidad' => 10,
            'precio_unitario_usado' => 50,
            'subtotal' => 500,
        ]);

        $resultado = $this->reporteService->calcularRentabilidadExamen([
            'fecha_inicio' => $fecha->format('Y-m-d'),
            'fecha_fin' => $fecha->format('Y-m-d'),
        ]);

        $this->assertGreaterThan(0, $resultado->count());
        $examenResult = $resultado->firstWhere('examen_id', $examen->id);
        $this->assertNotNull($examenResult);
        $this->assertEquals('Examen Test', $examenResult->nombre_examen);
        $this->assertEquals(10, $examenResult->cantidad_total);
        $this->assertEquals(500, $examenResult->total_ingresos);
        $this->assertEquals(50.0, $examenResult->ingreso_promedio);
    }

    /** @test */
    public function calcula_productividad_con_datos_basicos()
    {
        $fecha = now()->startOfDay();
        $clinica = Clinica::factory()->create();
        $examen = Examen::factory()->create();
        
        $repase = Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => $fecha,
        ]);

        RepaseExamen::factory()->create([
            'repase_id' => $repase->id,
            'examen_id' => $examen->id,
            'cantidad' => 20,
        ]);

        $resultado = $this->reporteService->calcularProductividad([
            'fecha_inicio' => $fecha->format('Y-m-d'),
            'fecha_fin' => $fecha->format('Y-m-d'),
        ]);

        $this->assertEquals(20, $resultado['total_examenes_realizados']);
        $this->assertEquals(1, $resultado['total_repases']);
        $this->assertEquals(20.0, $resultado['examenes_por_repase']);
        $this->assertIsArray($resultado['por_examen']);
        $this->assertIsArray($resultado['por_clinica']);
    }

    /** @test */
    public function calcula_comparativo_entre_dos_periodos()
    {
        $fechaActual = now()->startOfDay();
        $fechaAnterior = now()->subMonth()->startOfDay();
        $clinica = Clinica::factory()->create();
        
        // Período anterior
        Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => $fechaAnterior,
            'total_examenes' => 1000,
            'total_consultas' => 500,
            'total_gastos' => 600,
            'total_neto' => 900,
        ]);

        // Período actual
        Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => $fechaActual,
            'total_examenes' => 1500,
            'total_consultas' => 750,
            'total_gastos' => 900,
            'total_neto' => 1350,
        ]);

        $resultado = $this->reporteService->calcularComparativo(
            [
                'fecha_inicio' => $fechaActual->format('Y-m-d'),
                'fecha_fin' => $fechaActual->format('Y-m-d'),
            ],
            [
                'fecha_inicio' => $fechaAnterior->format('Y-m-d'),
                'fecha_fin' => $fechaAnterior->format('Y-m-d'),
            ]
        );

        $this->assertEquals(2250, $resultado['periodo_actual']['total_ingresos']);
        $this->assertEquals(1500, $resultado['periodo_anterior']['total_ingresos']);
        $this->assertEquals(50.0, $resultado['variaciones']['ingresos_variacion']);
    }
}
