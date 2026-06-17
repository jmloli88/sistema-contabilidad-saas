<?php

namespace Tests\Feature;

use App\Models\Agenda;
use App\Models\Clinica;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AgendaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->user->subscriptions()->create([
            'type' => 'default',
            'stripe_id' => 'sub_agenda_test',
            'stripe_status' => 'active',
            'stripe_price' => 'price_test',
            'ends_at' => now()->addDays(30),
        ]);
    }

    public function test_puede_ver_calendario_agendas(): void
    {
        $response = $this->actingAs($this->user)->get(route('agendas.index'));
        $response->assertStatus(200);
        $response->assertViewIs('agendas.index');
    }

    public function test_puede_crear_agenda_unica(): void
    {
        $clinica = Clinica::factory()->create();

        $response = $this->actingAs($this->user)->postJson(route('agendas.store'), [
            'clinica_id' => $clinica->id,
            'fecha' => Carbon::tomorrow()->format('Y-m-d'),
            'hora_inicio' => '08:00',
            'hora_fin' => '13:00',
            'doctor' => 'Dr. Test',
            'tipo_repeticion' => 'unica',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('agendas', [
            'clinica_id' => $clinica->id,
            'doctor' => 'Dr. Test',
            'tipo_repeticion' => 'unica',
        ]);
    }

    public function test_detecta_conflicto_horario(): void
    {
        $clinica = Clinica::factory()->create();
        $fecha = Carbon::tomorrow()->format('Y-m-d');

        // Crear primera agenda
        Agenda::create([
            'clinica_id' => $clinica->id,
            'fecha' => $fecha,
            'hora_inicio' => '08:00',
            'hora_fin' => '13:00',
            'doctor' => 'Dr. Test 1',
            'tipo_repeticion' => 'unica',
        ]);

        // Intentar crear agenda con conflicto
        $response = $this->actingAs($this->user)->postJson(route('agendas.store'), [
            'clinica_id' => $clinica->id,
            'fecha' => $fecha,
            'hora_inicio' => '10:00',
            'hora_fin' => '15:00',
            'doctor' => 'Dr. Test 2',
            'tipo_repeticion' => 'unica',
        ]);

        $response->assertStatus(422);
        $response->assertJson(['success' => false]);
    }

    public function test_puede_crear_agendas_repetitivas(): void
    {
        $clinica = Clinica::factory()->create();

        $response = $this->actingAs($this->user)->postJson(route('agendas.store'), [
            'clinica_id' => $clinica->id,
            'fecha' => Carbon::tomorrow()->format('Y-m-d'),
            'hora_inicio' => '08:00',
            'hora_fin' => '13:00',
            'doctor' => 'Dr. Test',
            'tipo_repeticion' => 'repetitiva',
            'dias_repeticion' => 7,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        
        // Verificar que se crearon múltiples agendas
        $this->assertGreaterThan(1, Agenda::where('clinica_id', $clinica->id)->count());
    }

    public function test_omite_domingos_en_agendas_repetitivas(): void
    {
        $clinica = Clinica::factory()->create();
        
        // Encontrar el próximo domingo
        $proximoDomingo = Carbon::now();
        while (!$proximoDomingo->isSunday()) {
            $proximoDomingo->addDay();
        }
        
        // Crear agenda que caería en domingo
        $fechaInicio = $proximoDomingo->copy()->subDays(7);

        $response = $this->actingAs($this->user)->postJson(route('agendas.store'), [
            'clinica_id' => $clinica->id,
            'fecha' => $fechaInicio->format('Y-m-d'),
            'hora_inicio' => '08:00',
            'hora_fin' => '13:00',
            'doctor' => 'Dr. Test',
            'tipo_repeticion' => 'repetitiva',
            'dias_repeticion' => 7,
        ]);

        $response->assertStatus(200);
        
        // Verificar que no hay agendas en domingo
        $agendas = Agenda::where('clinica_id', $clinica->id)->get();
        foreach ($agendas as $agenda) {
            $this->assertFalse(Carbon::parse($agenda->fecha)->isSunday());
        }
    }

    public function test_puede_actualizar_agenda(): void
    {
        $clinica = Clinica::factory()->create();
        $agenda = Agenda::factory()->create([
            'clinica_id' => $clinica->id,
        ]);

        $response = $this->actingAs($this->user)->putJson(route('agendas.update', $agenda), [
            'clinica_id' => $clinica->id,
            'fecha' => $agenda->fecha->format('Y-m-d'),
            'hora_inicio' => '14:00',
            'hora_fin' => '18:00',
            'doctor' => 'Dr. Actualizado',
            'aplicar_a_todas' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('agendas', [
            'id' => $agenda->id,
            'doctor' => 'Dr. Actualizado',
            'hora_inicio' => '14:00',
        ]);
    }

    public function test_puede_eliminar_agenda(): void
    {
        $agenda = Agenda::factory()->create();

        $response = $this->actingAs($this->user)->deleteJson(route('agendas.destroy', $agenda), [
            'eliminar_todas' => false,
        ]);

        $response->assertStatus(200);
        $response->assertJson(['success' => true]);
        $this->assertDatabaseMissing('agendas', ['id' => $agenda->id]);
    }

    public function test_puede_obtener_eventos_calendario(): void
    {
        $clinica = Clinica::factory()->create();
        Agenda::factory()->count(3)->create(['clinica_id' => $clinica->id]);

        $response = $this->actingAs($this->user)->getJson(route('agendas.events'));

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }
}
