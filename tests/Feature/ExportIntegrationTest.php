<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Clinica;
use App\Models\Repase;
use App\Services\Reportes\ExportService;
use App\Services\Reportes\ReporteService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExportIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected ExportService $exportService;
    protected ReporteService $reporteService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportService = new ExportService();
        $this->reporteService = new ReporteService();
    }

    /** @test */
    public function puede_exportar_rentabilidad_clinica_a_excel()
    {
        // Crear datos de prueba
        $clinica = Clinica::factory()->create(['nombre' => 'Clínica Test']);
        
        Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => '2024-01-15',
            'total_examenes' => 1000,
            'total_consultas' => 500,
            'total_gastos' => 600,
            'total_neto' => 900,
        ]);

        // Calcular reporte
        $datos = $this->reporteService->calcularRentabilidadClinica([
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
        ]);

        // Exportar a Excel
        $rutaArchivo = $this->exportService->exportarExcel('rentabilidad-clinica', $datos, [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
        ]);

        // Verificar que el archivo existe
        $this->assertFileExists($rutaArchivo);
        $this->assertStringEndsWith('.xlsx', $rutaArchivo);

        // Verificar que el archivo tiene contenido
        $this->assertGreaterThan(0, filesize($rutaArchivo));

        // Limpiar
        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }
    }

    /** @test */
    public function puede_exportar_rentabilidad_examen_a_excel()
    {
        // Crear datos de prueba
        $clinica = Clinica::factory()->create();
        $repase = Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => '2024-01-15',
        ]);

        // Calcular reporte
        $datos = $this->reporteService->calcularRentabilidadExamen([
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
        ]);

        // Exportar a Excel
        $rutaArchivo = $this->exportService->exportarExcel('rentabilidad-examen', $datos, [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
        ]);

        // Verificar
        $this->assertFileExists($rutaArchivo);
        $this->assertGreaterThan(0, filesize($rutaArchivo));

        // Limpiar
        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }
    }

    /** @test */
    public function puede_exportar_productividad_a_excel()
    {
        // Crear datos de prueba
        $clinica = Clinica::factory()->create();
        Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => '2024-01-15',
        ]);

        // Calcular reporte
        $datos = $this->reporteService->calcularProductividad([
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
        ]);

        // Exportar a Excel
        $rutaArchivo = $this->exportService->exportarExcel('productividad', $datos, [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
        ]);

        // Verificar
        $this->assertFileExists($rutaArchivo);
        $this->assertGreaterThan(0, filesize($rutaArchivo));

        // Limpiar
        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }
    }

    /** @test */
    public function puede_exportar_comparativo_a_excel()
    {
        // Crear datos de prueba
        $clinica = Clinica::factory()->create();
        
        Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => '2024-01-15',
            'total_examenes' => 1000,
            'total_consultas' => 500,
            'total_gastos' => 600,
            'total_neto' => 900,
        ]);

        Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => '2023-12-15',
            'total_examenes' => 800,
            'total_consultas' => 400,
            'total_gastos' => 500,
            'total_neto' => 700,
        ]);

        // Calcular reporte
        $datos = $this->reporteService->calcularComparativo(
            ['fecha_inicio' => '2024-01-01', 'fecha_fin' => '2024-01-31'],
            ['fecha_inicio' => '2023-12-01', 'fecha_fin' => '2023-12-31']
        );

        // Exportar a Excel
        $rutaArchivo = $this->exportService->exportarExcel('comparativo', $datos, [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
        ]);

        // Verificar
        $this->assertFileExists($rutaArchivo);
        $this->assertGreaterThan(0, filesize($rutaArchivo));

        // Limpiar
        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }
    }

    /** @test */
    public function puede_exportar_a_pdf()
    {
        // Crear datos de prueba
        $clinica = Clinica::factory()->create(['nombre' => 'Clínica Test']);
        
        Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => '2024-01-15',
            'total_examenes' => 1000,
            'total_consultas' => 500,
            'total_gastos' => 600,
            'total_neto' => 900,
        ]);

        // Calcular reporte
        $datos = $this->reporteService->calcularRentabilidadClinica([
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
        ]);

        // Exportar a PDF
        $rutaArchivo = $this->exportService->exportarPdf('rentabilidad-clinica', $datos, [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
        ]);

        // Verificar que el archivo existe
        $this->assertFileExists($rutaArchivo);
        $this->assertStringEndsWith('.pdf', $rutaArchivo);

        // Verificar que el archivo tiene contenido
        $this->assertGreaterThan(0, filesize($rutaArchivo));

        // Verificar que es un PDF válido (comienza con %PDF)
        $contenido = file_get_contents($rutaArchivo);
        $this->assertStringStartsWith('%PDF', $contenido);

        // Limpiar
        if (file_exists($rutaArchivo)) {
            unlink($rutaArchivo);
        }
    }

    protected function tearDown(): void
    {
        // Limpiar directorio temp
        $tempDir = storage_path('app/temp');
        if (file_exists($tempDir)) {
            $files = glob("$tempDir/*");
            foreach ($files as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        parent::tearDown();
    }
}
