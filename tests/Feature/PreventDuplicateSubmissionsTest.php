<?php

namespace Tests\Feature;

use App\Models\Clinica;
use App\Models\Examen;
use App\Models\Repase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class PreventDuplicateSubmissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Clinica $clinica;
    protected Examen $examen;
    protected \App\Models\Empresa $empresa;

    protected function setUp(): void
    {
        parent::setUp();

        $this->empresa = \App\Models\Empresa::factory()->create(['nombre' => 'Test Empresa Duplicate']);

        // Crear usuario admin bajo la misma empresa
        $this->admin = User::factory()->create([
            'empresa_id' => $this->empresa->id,
            'role' => 'administrador',
        ]);
        // Give the empresa a subscription so subscription middleware passes
        $this->empresa->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_admin_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);

        // Crear clínica bajo la misma empresa
        $this->clinica = Clinica::factory()->create([
            'empresa_id' => $this->empresa->id,
        ]);

        // Crear examen bajo la misma empresa
        $this->examen = Examen::factory()->create([
            'empresa_id' => $this->empresa->id,
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 150.00,
        ]);
    }

    /** @test */
    public function puede_crear_repase_con_token_unico()
    {
        $token = 'test_token_' . time();
        
        $response = $this->actingAs($this->admin)->post(route('repases.store'), [
            '_submission_token' => $token,
            'clinica_id' => $this->clinica->id,
            'fecha' => now()->format('Y-m-d'),
            'tipo_precio' => 'sin_nota',
            'estado' => 'pendiente',
            'total_consultas' => 10,
            'pedidos_doctor' => 5,
            'examenes' => [
                $this->examen->id => [
                    'cantidad' => 5,
                    'examen_id' => $this->examen->id,
                ],
            ],
            'gastos' => [],
            'comentarios' => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repases', [
            'clinica_id' => $this->clinica->id,
            'fecha' => now()->format('Y-m-d'),
        ]);
    }

    /** @test */
    public function previene_envio_duplicado_con_mismo_token()
    {
        $token = 'test_token_' . time();
        
        $data = [
            '_submission_token' => $token,
            'clinica_id' => $this->clinica->id,
            'fecha' => now()->format('Y-m-d'),
            'tipo_precio' => 'sin_nota',
            'estado' => 'pendiente',
            'total_consultas' => 10,
            'pedidos_doctor' => 5,
            'examenes' => [
                $this->examen->id => [
                    'cantidad' => 5,
                    'examen_id' => $this->examen->id,
                ],
            ],
            'gastos' => [],
            'comentarios' => [],
        ];

        // Primer envío - debe funcionar
        $response1 = $this->actingAs($this->admin)->post(route('repases.store'), $data);
        $response1->assertRedirect();

        // Segundo envío con mismo token - debe ser rechazado
        $response2 = $this->actingAs($this->admin)->post(route('repases.store'), $data);
        $response2->assertRedirect();
        $response2->assertSessionHas('error');

        // Verificar que solo se creó un repase
        $this->assertEquals(1, Repase::count());
    }

    /** @test */
    public function permite_envio_sin_token_para_compatibilidad()
    {
        $response = $this->actingAs($this->admin)->post(route('repases.store'), [
            // Sin _submission_token
            'clinica_id' => $this->clinica->id,
            'fecha' => now()->format('Y-m-d'),
            'tipo_precio' => 'sin_nota',
            'estado' => 'pendiente',
            'total_consultas' => 10,
            'pedidos_doctor' => 5,
            'examenes' => [
                $this->examen->id => [
                    'cantidad' => 5,
                    'examen_id' => $this->examen->id,
                ],
            ],
            'gastos' => [],
            'comentarios' => [],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('repases', [
            'clinica_id' => $this->clinica->id,
        ]);
    }

    /** @test */
    public function detecta_repase_duplicado_en_controlador()
    {
        // Crear primer repase (uses the clinica which is already under the same empresa)
        $repase1 = Repase::factory()->create([
            'clinica_id' => $this->clinica->id,
            'fecha' => now()->format('Y-m-d'),
            'created_at' => now(),
        ]);

        // Intentar crear segundo repase inmediatamente (dentro de 30 segundos)
        $response = $this->actingAs($this->admin)->post(route('repases.store'), [
            '_submission_token' => 'unique_token_' . time(),
            'clinica_id' => $this->clinica->id,
            'fecha' => now()->format('Y-m-d'),
            'tipo_precio' => 'sin_nota',
            'estado' => 'pendiente',
            'total_consultas' => 10,
            'pedidos_doctor' => 5,
            'examenes' => [
                $this->examen->id => [
                    'cantidad' => 5,
                    'examen_id' => $this->examen->id,
                ],
            ],
            'gastos' => [],
            'comentarios' => [],
        ]);

        // Debe redirigir al repase existente
        $response->assertRedirect(route('repases.show', $repase1));
        $response->assertSessionHas('warning');

        // Verificar que no se creó un segundo repase
        $this->assertEquals(1, Repase::count());
    }

    /** @test */
    public function permite_crear_repase_despues_de_30_segundos()
    {
        // Crear primer repase hace 31 segundos
        $repase1 = Repase::factory()->create([
            'clinica_id' => $this->clinica->id,
            'fecha' => now()->format('Y-m-d'),
            'created_at' => now()->subSeconds(31),
        ]);

        // Intentar crear segundo repase (fuera de ventana de 30 segundos)
        $response = $this->actingAs($this->admin)->post(route('repases.store'), [
            '_submission_token' => 'unique_token_' . time(),
            'clinica_id' => $this->clinica->id,
            'fecha' => now()->format('Y-m-d'),
            'tipo_precio' => 'sin_nota',
            'estado' => 'pendiente',
            'total_consultas' => 10,
            'pedidos_doctor' => 5,
            'examenes' => [
                $this->examen->id => [
                    'cantidad' => 5,
                    'examen_id' => $this->examen->id,
                ],
            ],
            'gastos' => [],
            'comentarios' => [],
        ]);

        $response->assertRedirect();
        
        // Debe permitir crear el segundo repase
        $this->assertEquals(2, Repase::count());
    }

    protected function tearDown(): void
    {
        // Limpiar caché después de cada test
        Cache::flush();
        
        parent::tearDown();
    }
}
