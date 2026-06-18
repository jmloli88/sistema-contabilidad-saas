<?php

namespace Database\Factories;

use App\Models\Empresa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Examen>
 */
class ExamenFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $precioSinNota = fake()->randomFloat(2, 80, 180);
        $precioConNota = $precioSinNota + fake()->randomFloat(2, 15, 40);

        return [
            'nombre' => fake()->words(3, true) . ' - Examen',
            'precio_sin_nota' => $precioSinNota,
            'precio_con_nota' => $precioConNota,
            'empresa_id' => Empresa::factory(),
            'is_active' => true,
        ];
    }
}
