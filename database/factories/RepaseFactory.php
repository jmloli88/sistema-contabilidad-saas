<?php

namespace Database\Factories;

use App\Models\Clinica;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Repase>
 */
class RepaseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $totalExamenes = fake()->randomFloat(2, 100, 1000);
        $totalConsultas = fake()->randomFloat(2, 0, 500);
        $totalGastos = fake()->randomFloat(2, 50, 400);
        $totalNeto = $totalExamenes + $totalConsultas - $totalGastos;

        return [
            'clinica_id' => Clinica::factory(),
            'fecha' => fake()->dateTimeBetween('-6 months', 'now'),
            'fecha_pago' => null,
            'estado' => 'pendiente',
            'tipo_precio' => fake()->randomElement(['sin_nota', 'con_nota']),
            'total_examenes' => $totalExamenes,
            'total_consultas' => $totalConsultas,
            'total_gastos' => $totalGastos,
            'total_neto' => $totalNeto,
            'observaciones' => fake()->optional(0.3)->sentence(),
        ];
    }

    /**
     * Estado para repases pagados.
     *
     * @return static
     */
    public function pagado(): static
    {
        return $this->state(function (array $attributes) {
            $fechaPago = fake()->dateTimeBetween($attributes['fecha'], 'now');
            
            return [
                'fecha_pago' => $fechaPago,
                'estado' => 'pagado',
            ];
        });
    }
}
