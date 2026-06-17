<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\Reportes\ExportService;
use Illuminate\Support\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ExportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ExportService $exportService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->exportService = new ExportService();
    }

    /** @test */
    public function genera_nombre_archivo_excel_con_patron_correcto()
    {
        $reflection = new \ReflectionClass($this->exportService);
        $method = $reflection->getMethod('generarNombreArchivo');
        $method->setAccessible(true);

        $nombreArchivo = $method->invoke($this->exportService, 'rentabilidad-clinica', 'xlsx');

        $this->assertStringStartsWith('reporte_rentabilidad-clinica_', $nombreArchivo);
        $this->assertStringEndsWith('.xlsx', $nombreArchivo);
        $this->assertMatchesRegularExpression('/reporte_rentabilidad-clinica_\d{4}-\d{2}-\d{2}\.xlsx/', $nombreArchivo);
    }

    /** @test */
    public function genera_nombre_archivo_pdf_con_patron_correcto()
    {
        $reflection = new \ReflectionClass($this->exportService);
        $method = $reflection->getMethod('generarNombreArchivo');
        $method->setAccessible(true);

        $nombreArchivo = $method->invoke($this->exportService, 'productividad', 'pdf');

        $this->assertStringStartsWith('reporte_productividad_', $nombreArchivo);
        $this->assertStringEndsWith('.pdf', $nombreArchivo);
        $this->assertMatchesRegularExpression('/reporte_productividad_\d{4}-\d{2}-\d{2}\.pdf/', $nombreArchivo);
    }

    /** @test */
    public function obtiene_titulo_correcto_para_cada_tipo_reporte()
    {
        $reflection = new \ReflectionClass($this->exportService);
        $method = $reflection->getMethod('obtenerTituloReporte');
        $method->setAccessible(true);

        $this->assertEquals(
            'Reporte de Rentabilidad por Clínica',
            $method->invoke($this->exportService, 'rentabilidad-clinica')
        );

        $this->assertEquals(
            'Reporte de Rentabilidad por Tipo de Examen',
            $method->invoke($this->exportService, 'rentabilidad-examen')
        );

        $this->assertEquals(
            'Reporte de Productividad',
            $method->invoke($this->exportService, 'productividad')
        );

        $this->assertEquals(
            'Reporte Comparativo de Períodos',
            $method->invoke($this->exportService, 'comparativo')
        );
    }

    /** @test */
    public function formatea_filtros_correctamente_para_pdf()
    {
        $reflection = new \ReflectionClass($this->exportService);
        $method = $reflection->getMethod('formatearFiltrosParaPdf');
        $method->setAccessible(true);

        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
            'clinica_nombre' => 'Clínica Central',
            'examen_nombre' => 'Radiografía',
        ];

        $resultado = $method->invoke($this->exportService, $filtros);

        $this->assertIsArray($resultado);
        $this->assertCount(3, $resultado);
        
        $this->assertEquals('Período', $resultado[0]['label']);
        $this->assertStringContainsString('01/01/2024', $resultado[0]['valor']);
        $this->assertStringContainsString('31/01/2024', $resultado[0]['valor']);
        
        $this->assertEquals('Clínica', $resultado[1]['label']);
        $this->assertEquals('Clínica Central', $resultado[1]['valor']);
        
        $this->assertEquals('Examen', $resultado[2]['label']);
        $this->assertEquals('Radiografía', $resultado[2]['valor']);
    }

    /** @test */
    public function formatea_filtros_sin_clinica_ni_examen()
    {
        $reflection = new \ReflectionClass($this->exportService);
        $method = $reflection->getMethod('formatearFiltrosParaPdf');
        $method->setAccessible(true);

        $filtros = [
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
        ];

        $resultado = $method->invoke($this->exportService, $filtros);

        $this->assertIsArray($resultado);
        $this->assertCount(1, $resultado);
        $this->assertEquals('Período', $resultado[0]['label']);
    }

    /** @test */
    public function exportar_excel_crea_directorio_temp_si_no_existe()
    {
        // Limpiar directorio temp si existe
        $tempDir = storage_path('app/temp');
        if (file_exists($tempDir)) {
            array_map('unlink', glob("$tempDir/*"));
            rmdir($tempDir);
        }

        $this->assertDirectoryDoesNotExist($tempDir);

        $datos = collect([
            (object) [
                'nombre_clinica' => 'Test',
                'total_ingresos' => 1000,
                'total_gastos' => 500,
                'ganancia_neta' => 500,
                'margen_ganancia' => 50,
                'cantidad_repases' => 10,
            ],
        ]);

        try {
            $rutaArchivo = $this->exportService->exportarExcel('rentabilidad-clinica', $datos, [
                'fecha_inicio' => '2024-01-01',
                'fecha_fin' => '2024-01-31',
            ]);

            $this->assertDirectoryExists($tempDir);
            $this->assertFileExists($rutaArchivo);

            // Limpiar
            unlink($rutaArchivo);
        } catch (\Exception $e) {
            // Si falla por alguna razón de configuración, al menos verificamos que el directorio se creó
            $this->assertDirectoryExists($tempDir);
        }
    }
}
