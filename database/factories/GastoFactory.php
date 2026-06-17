<?php

namespace Database\Factories;

use App\Models\Repase;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Gasto>
 */
class GastoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $tipo = fake()->randomElement(['doctor', 'tecnico', 'laudos', 'gasolina', 'extra']);
        
        return [
            'repase_id' => Repase::factory(),
            'tipo' => $tipo,
            'descripcion' => $tipo === 'extra' ? fake()->sentence(3) : fake()->optional(0.5)->sentence(3),
            'monto' => fake()->randomFloat(2, 10, 300),
        ];
    }
}
