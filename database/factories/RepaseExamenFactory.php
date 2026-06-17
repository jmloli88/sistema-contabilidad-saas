<?php

namespace Database\Factories;

use App\Models\Examen;
use App\Models\Repase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RepaseExamen>
 */
class RepaseExamenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $cantidad = fake()->numberBetween(1, 10);
        $precioUnitario = fake()->randomFloat(2, 100, 220);
        $subtotal = $cantidad * $precioUnitario;

        return [
            'repase_id' => Repase::factory(),
            'examen_id' => Examen::factory(),
            'cantidad' => $cantidad,
            'precio_unitario_usado' => $precioUnitario,
            'subtotal' => $subtotal,
        ];
    }
}
