<?php

namespace Database\Factories;

use App\Models\Agenda;
use App\Models\Clinica;
use Illuminate\Database\Eloquent\Factories\Factory;

class AgendaFactory extends Factory
{
    protected $model = Agenda::class;

    public function definition(): array
    {
        return [
            'clinica_id' => Clinica::factory(),
            'fecha' => $this->faker->dateTimeBetween('now', '+3 months'),
            'hora_inicio' => $this->faker->time('H:i', '12:00'),
            'hora_fin' => $this->faker->time('H:i', '18:00'),
            'doctor' => 'Dr. ' . $this->faker->name(),
            'tipo_repeticion' => 'unica',
            'dias_repeticion' => null,
            'grupo_repeticion' => null,
        ];
    }

    public function repetitiva(int $dias = 7): static
    {
        return $this->state(fn (array $attributes) => [
            'tipo_repeticion' => 'repetitiva',
            'dias_repeticion' => $dias,
            'grupo_repeticion' => uniqid('grupo_', true),
        ]);
    }
}
