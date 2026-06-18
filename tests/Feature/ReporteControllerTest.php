<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests para ReporteController
 * 
 * Verifica que el controlador de reportes funcione correctamente
 * y que el middleware de autorización esté aplicado.
 */
class ReporteControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminWithSubscription(array $extra = []): User
    {
        $empresa = \App\Models\Empresa::factory()->create(['nombre' => 'Admin ' . uniqid()]);
        $user = User::factory()->create(array_merge(['role' => 'administrador', 'empresa_id' => $empresa->id], $extra));
        $empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_admin_' . uniqid(),
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
        return $user;
    }

    private function createUserWithSubscription(array $extra = []): User
    {
        $empresa = \App\Models\Empresa::factory()->create(['nombre' => 'User ' . uniqid()]);
        $user = User::factory()->create(array_merge(['role' => 'usuario', 'empresa_id' => $empresa->id], $extra));
        $empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_user_' . uniqid(),
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
        return $user;
    }

    /**
     * Test: Usuario administrador puede acceder al dashboard de reportes
     * 
     * Valida Requirements 1.1, 2.1
     */
    public function test_admin_can_access_reportes_index(): void
    {
        // Crear usuario administrador
        $admin = $this->createAdminWithSubscription();

        // Actuar como administrador
        $response = $this->actingAs($admin)->get(route('reportes.index'));

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);
        $response->assertViewIs('reportes.index');
    }

    /**
     * Test: Usuario regular no puede acceder al dashboard de reportes
     * 
     * Valida Requirements 1.2, 1.3
     */
    public function test_regular_user_cannot_access_reportes_index(): void
    {
        // Crear usuario regular
        $user = $this->createUserWithSubscription();

        // Actuar como usuario regular
        $response = $this->actingAs($user)->get(route('reportes.index'));

        // Verificar que se reciba un 403 Forbidden
        $response->assertStatus(403);
    }

    /**
     * Test: Usuario no autenticado no puede acceder al dashboard de reportes
     * 
     * Valida Requirements 1.1
     */
    public function test_guest_cannot_access_reportes_index(): void
    {
        // Intentar acceder sin autenticación
        $response = $this->get(route('reportes.index'));

        // Verificar que se redirija al login
        $response->assertRedirect(route('login'));
    }

    /**
     * Test: Usuario administrador puede acceder al reporte comparativo
     * 
     * Valida Requirements 6.1, 6.2, 6.3, 6.4
     */
    public function test_admin_can_access_comparativo_report(): void
    {
        // Crear usuario administrador
        $admin = $this->createAdminWithSubscription();

        // Crear datos de prueba para ambos períodos
        $clinica = \App\Models\Clinica::factory()->create();
        
        // Datos para período actual (mes actual)
        \App\Models\Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => now()->format('Y-m-d'),
            'total_examenes' => 1000,
            'total_consultas' => 500,
            'total_gastos' => 300,
            'total_neto' => 1200,
        ]);

        // Datos para período anterior (mes anterior)
        \App\Models\Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => now()->subMonth()->format('Y-m-d'),
            'total_examenes' => 800,
            'total_consultas' => 400,
            'total_gastos' => 250,
            'total_neto' => 950,
        ]);

        // Actuar como administrador con fechas explícitas
        $response = $this->actingAs($admin)->get(route('reportes.comparativo', [
            'fecha_inicio_actual' => now()->startOfMonth()->format('Y-m-d'),
            'fecha_fin_actual' => now()->format('Y-m-d'),
            'fecha_inicio_anterior' => now()->subMonth()->startOfMonth()->format('Y-m-d'),
            'fecha_fin_anterior' => now()->subMonth()->endOfMonth()->format('Y-m-d'),
        ]));

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);
        $response->assertViewIs('reportes.comparativo');
        $response->assertViewHas('datos');
        $response->assertViewHas('filtros');
        $response->assertViewHas('clinicas');
    }

    /**
     * Test: Reporte comparativo valida fechas correctamente
     * 
     * Valida Requirements 14.3
     */
    public function test_comparativo_validates_date_order(): void
    {
        // Crear usuario administrador
        $admin = $this->createAdminWithSubscription();

        // Intentar con fecha_fin_actual antes de fecha_inicio_actual
        $response = $this->actingAs($admin)->get(route('reportes.comparativo', [
            'fecha_inicio_actual' => '2024-12-31',
            'fecha_fin_actual' => '2024-01-01',
        ]));

        // Verificar que haya errores de validación
        $response->assertSessionHasErrors('fecha_fin_actual');
    }

    /**
     * Test: Exportación a Excel requiere tipo de reporte válido
     * 
     * Valida Requirements 9.1, 14.7
     */
    public function test_export_excel_validates_tipo_reporte(): void
    {
        // Crear usuario administrador
        $admin = $this->createAdminWithSubscription();

        // Intentar exportar con tipo inválido
        $response = $this->actingAs($admin)->post(route('reportes.export.excel'), [
            'tipo' => 'tipo-invalido',
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
        ]);

        // Verificar que haya errores de validación
        $response->assertSessionHasErrors('tipo');
    }

    /**
     * Test: Exportación a Excel genera archivo con nombre correcto
     * 
     * Valida Requirements 9.6, 9.7
     */
    public function test_export_excel_generates_file_with_correct_name(): void
    {
        // Crear usuario administrador
        $admin = $this->createAdminWithSubscription();

        // Crear datos de prueba
        $clinica = \App\Models\Clinica::factory()->create();
        \App\Models\Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => now()->format('Y-m-d'),
            'total_examenes' => 1000,
            'total_consultas' => 500,
            'total_gastos' => 300,
            'total_neto' => 1200,
        ]);

        // Exportar reporte
        $response = $this->actingAs($admin)->post(route('reportes.export.excel'), [
            'tipo' => 'rentabilidad-clinica',
            'fecha_inicio' => now()->startOfMonth()->format('Y-m-d'),
            'fecha_fin' => now()->format('Y-m-d'),
        ]);

        // Verificar que se descargue un archivo
        $response->assertDownload();
        
        // Verificar que el nombre del archivo siga el patrón correcto
        $contentDisposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('reporte_rentabilidad-clinica_', $contentDisposition);
        $this->assertStringContainsString('.xlsx', $contentDisposition);
    }

    /**
     * Test: Exportación a Excel valida parámetros según tipo de reporte
     * 
     * Valida Requirements 14.1, 14.2, 14.3
     */
    public function test_export_excel_validates_parameters_by_report_type(): void
    {
        // Crear usuario administrador
        $admin = $this->createAdminWithSubscription();

        // Intentar exportar reporte comparativo sin parámetros requeridos
        $response = $this->actingAs($admin)->post(route('reportes.export.excel'), [
            'tipo' => 'comparativo',
            // Faltan fecha_inicio_actual, fecha_fin_actual, etc.
        ]);

        // Verificar que haya errores de validación
        $response->assertSessionHasErrors(['fecha_inicio_actual', 'fecha_fin_actual', 'fecha_inicio_anterior', 'fecha_fin_anterior']);
    }

    /**
     * Test: Exportación a PDF requiere tipo de reporte válido
     * 
     * Valida Requirements 10.1, 14.7
     */
    public function test_export_pdf_validates_tipo_reporte(): void
    {
        // Crear usuario administrador
        $admin = $this->createAdminWithSubscription();

        // Intentar exportar con tipo inválido
        $response = $this->actingAs($admin)->post(route('reportes.export.pdf'), [
            'tipo' => 'tipo-invalido',
            'fecha_inicio' => '2024-01-01',
            'fecha_fin' => '2024-01-31',
        ]);

        // Verificar que haya errores de validación
        $response->assertSessionHasErrors('tipo');
    }

    /**
     * Test: Exportación a PDF genera archivo con nombre correcto
     * 
     * Valida Requirements 10.7, 10.8
     */
    public function test_export_pdf_generates_file_with_correct_name(): void
    {
        // Crear usuario administrador
        $admin = $this->createAdminWithSubscription();

        // Crear datos de prueba
        $clinica = \App\Models\Clinica::factory()->create();
        \App\Models\Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => now()->format('Y-m-d'),
            'total_examenes' => 1000,
            'total_consultas' => 500,
            'total_gastos' => 300,
            'total_neto' => 1200,
        ]);

        // Exportar reporte
        $response = $this->actingAs($admin)->post(route('reportes.export.pdf'), [
            'tipo' => 'rentabilidad-clinica',
            'fecha_inicio' => now()->startOfMonth()->format('Y-m-d'),
            'fecha_fin' => now()->format('Y-m-d'),
        ]);

        // Verificar que se descargue un archivo
        $response->assertDownload();
        
        // Verificar que el nombre del archivo siga el patrón correcto
        $contentDisposition = $response->headers->get('content-disposition');
        $this->assertStringContainsString('reporte_rentabilidad-clinica_', $contentDisposition);
        $this->assertStringContainsString('.pdf', $contentDisposition);
    }

    /**
     * Test: Exportación a PDF acepta parámetro opcional de gráficos
     * 
     * Valida Requirements 10.5
     */
    public function test_export_pdf_accepts_optional_graficos_parameter(): void
    {
        // Crear usuario administrador
        $admin = $this->createAdminWithSubscription();

        // Crear datos de prueba
        $clinica = \App\Models\Clinica::factory()->create();
        \App\Models\Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => now()->format('Y-m-d'),
            'total_examenes' => 1000,
            'total_consultas' => 500,
            'total_gastos' => 300,
            'total_neto' => 1200,
        ]);

        // Exportar reporte con gráficos
        $response = $this->actingAs($admin)->post(route('reportes.export.pdf'), [
            'tipo' => 'rentabilidad-clinica',
            'fecha_inicio' => now()->startOfMonth()->format('Y-m-d'),
            'fecha_fin' => now()->format('Y-m-d'),
            'graficos' => [
                'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==',
            ],
        ]);

        // Verificar que se descargue un archivo
        $response->assertDownload();
    }
}
