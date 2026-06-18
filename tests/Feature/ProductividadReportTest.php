<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Clinica;
use App\Models\Repase;
use App\Models\Examen;
use App\Models\RepaseExamen;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests para el reporte de productividad
 * 
 * Verifica que el método productividad del ReporteController funcione correctamente
 */
class ProductividadReportTest extends TestCase
{
    use RefreshDatabase;

    private function createAdminWithSubscription(): User
    {
        $empresa = \App\Models\Empresa::factory()->create(['nombre' => 'Admin ' . uniqid()]);
        $user = User::factory()->create(['role' => 'administrador', 'empresa_id' => $empresa->id]);
        $empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_admin_' . uniqid(),
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
        return $user;
    }

    private function createUserWithSubscription(): User
    {
        $empresa = \App\Models\Empresa::factory()->create(['nombre' => 'User ' . uniqid()]);
        $user = User::factory()->create(['role' => 'usuario', 'empresa_id' => $empresa->id]);
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
     * Test: Administrador puede acceder al reporte de productividad
     * 
     * Valida Requirements 5.1, 5.2, 5.3, 5.4, 5.5, 5.6
     */
    public function test_admin_can_access_productividad_report(): void
    {
        // Crear usuario administrador
        $admin = $this->createAdminWithSubscription();

        // Crear datos de prueba con fecha específica
        $clinica = Clinica::factory()->create();
        $examen = Examen::factory()->create();
        
        $fechaRepase = now()->subDays(5)->format('Y-m-d');
        
        $repase = Repase::factory()->create([
            'clinica_id' => $clinica->id,
            'fecha' => $fechaRepase,
        ]);

        RepaseExamen::factory()->create([
            'repase_id' => $repase->id,
            'examen_id' => $examen->id,
            'cantidad' => 5,
        ]);

        // Actuar como administrador y acceder al reporte con rango que incluye el repase
        $response = $this->actingAs($admin)->get(route('reportes.productividad', [
            'fecha_inicio' => now()->subDays(10)->format('Y-m-d'),
            'fecha_fin' => now()->format('Y-m-d'),
        ]));

        // Verificar que la respuesta sea exitosa
        $response->assertStatus(200);
        $response->assertViewIs('reportes.productividad');
        $response->assertViewHas('datos');
        $response->assertViewHas('filtros');
        $response->assertViewHas('clinicas');

        // Verificar que los datos contengan las métricas esperadas
        $datos = $response->viewData('datos');
        $this->assertArrayHasKey('total_examenes_realizados', $datos);
        $this->assertArrayHasKey('examenes_por_dia', $datos);
        $this->assertArrayHasKey('total_repases', $datos);
        $this->assertArrayHasKey('examenes_por_repase', $datos);
        $this->assertArrayHasKey('por_examen', $datos);
        $this->assertArrayHasKey('por_clinica', $datos);
        
        // Verificar que hay datos
        $this->assertGreaterThan(0, $datos['total_examenes_realizados']);
    }

    /**
     * Test: Usuario regular no puede acceder al reporte de productividad
     * 
     * Valida Requirements 1.2, 1.3
     */
    public function test_regular_user_cannot_access_productividad_report(): void
    {
        // Crear usuario regular
        $user = $this->createUserWithSubscription();

        // Actuar como usuario regular
        $response = $this->actingAs($user)->get(route('reportes.productividad'));

        // Verificar que se reciba un 403 Forbidden
        $response->assertStatus(403);
    }

    /**
     * Test: Reporte muestra mensaje cuando no hay datos
     * 
     * Valida Requirements 17.5
     */
    public function test_productividad_shows_warning_when_no_data(): void
    {
        // Crear usuario administrador
        $admin = $this->createAdminWithSubscription();

        // Actuar como administrador con fechas sin datos
        $response = $this->actingAs($admin)->get(route('reportes.productividad', [
            'fecha_inicio' => '2020-01-01',
            'fecha_fin' => '2020-01-31',
        ]));

        // Verificar que se redirija con mensaje de advertencia
        $response->assertRedirect();
        $response->assertSessionHas('warning', 'No se encontraron datos para los filtros seleccionados');
    }

    /**
     * Test: Validación rechaza fecha_inicio posterior a fecha_fin
     * 
     * Valida Requirements 14.3
     */
    public function test_productividad_validates_date_order(): void
    {
        // Crear usuario administrador
        $admin = $this->createAdminWithSubscription();

        // Actuar como administrador con fechas inválidas
        $response = $this->actingAs($admin)->get(route('reportes.productividad', [
            'fecha_inicio' => '2024-12-31',
            'fecha_fin' => '2024-01-01',
        ]));

        // Verificar que haya errores de validación
        $response->assertSessionHasErrors('fecha_fin');
    }
}
