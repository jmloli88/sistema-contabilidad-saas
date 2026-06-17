<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Clinica;
use App\Models\Examen;

class ExamenPriceResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_override_wins_when_pivot_has_non_null_precio_sin_nota(): void
    {
        // REQ-PRICE-002: GIVEN Pivot has precio_sin_nota=150 for clinic+exam
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 150.00,
        ]);
        $clinica = Clinica::factory()->create();
        $examen->clinicas()->attach($clinica->id, [
            'precio_sin_nota' => 150.00,
            'precio_con_nota' => 200.00,
        ]);

        // WHEN getPrecioParaClinica(clinicId, 'sin_nota')
        $price = $examen->getPrecioParaClinica($clinica->id, 'sin_nota');

        // THEN returns 150 (override wins)
        $this->assertSame(150.00, $price);
    }

    public function test_override_wins_for_precio_con_nota(): void
    {
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 150.00,
        ]);
        $clinica = Clinica::factory()->create();
        $examen->clinicas()->attach($clinica->id, [
            'precio_sin_nota' => 180.00,
            'precio_con_nota' => 250.00,
        ]);

        $price = $examen->getPrecioParaClinica($clinica->id, 'con_nota');

        $this->assertSame(250.00, $price);
    }

    public function test_fallback_on_null_pivot_returns_global_precio_sin_nota(): void
    {
        // REQ-PRICE-003: GIVEN Pivot row exists with precio_sin_nota=NULL
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 150.00,
        ]);
        $clinica = Clinica::factory()->create();
        $examen->clinicas()->attach($clinica->id, [
            'precio_sin_nota' => null,
            'precio_con_nota' => null,
        ]);

        // WHEN getPrecioParaClinica(clinicId, 'sin_nota')
        $price = $examen->getPrecioParaClinica($clinica->id, 'sin_nota');

        // THEN returns examen.precio_sin_nota (global fallback)
        $this->assertSame(100.00, $price);
    }

    public function test_fallback_on_missing_pivot_returns_global_precio_sin_nota(): void
    {
        // REQ-PRICE-003: GIVEN No pivot row for clinic+exam
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 150.00,
        ]);
        $clinica = Clinica::factory()->create();
        // No attach — no pivot row exists

        // WHEN getPrecioParaClinica(clinicId, 'sin_nota')
        $price = $examen->getPrecioParaClinica($clinica->id, 'sin_nota');

        // THEN returns examen.precio_sin_nota (global fallback)
        $this->assertSame(100.00, $price);
    }

    public function test_null_clinica_id_returns_global_precio_sin_nota(): void
    {
        // REQ-PRICE-002: null clinicaId returns global price
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 150.00,
        ]);
        // No clinica needed — we pass null

        $price = $examen->getPrecioParaClinica(null, 'sin_nota');

        $this->assertSame(100.00, $price);
    }

    public function test_null_clinica_id_returns_global_precio_con_nota(): void
    {
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 150.00,
        ]);

        $price = $examen->getPrecioParaClinica(null, 'con_nota');

        $this->assertSame(150.00, $price);
    }

    public function test_get_precios_para_clinica_returns_both_prices_as_array(): void
    {
        // REQ-PRICE-002 + REQ-PRICE-003: getPreciosParaClinica returns both prices
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 150.00,
        ]);
        $clinica = Clinica::factory()->create();
        $examen->clinicas()->attach($clinica->id, [
            'precio_sin_nota' => 200.00,
            'precio_con_nota' => null,  // fallback for con_nota
        ]);

        $prices = $examen->getPreciosParaClinica($clinica->id);

        // THEN returns ['sin_nota' => X, 'con_nota' => Y]
        $this->assertIsArray($prices);
        $this->assertArrayHasKey('sin_nota', $prices);
        $this->assertArrayHasKey('con_nota', $prices);
        $this->assertSame(200.00, $prices['sin_nota']);   // override wins
        $this->assertSame(150.00, $prices['con_nota']);   // global fallback
    }

    public function test_get_precios_para_clinica_with_null_returns_global(): void
    {
        $examen = Examen::factory()->create([
            'precio_sin_nota' => 100.00,
            'precio_con_nota' => 150.00,
        ]);

        $prices = $examen->getPreciosParaClinica(null);

        $this->assertSame(100.00, $prices['sin_nota']);
        $this->assertSame(150.00, $prices['con_nota']);
    }
}
