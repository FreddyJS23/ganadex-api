<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vacuna>
 */
class VacunaFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->word(),
            'intervalo_dosis' => fake()->numberBetween(1, 100),
            'dosis_recomendada_anual' => fake()->numberBetween(1, 5),
            'tipo_vacuna' => fake()->randomElement(['medica', 'plan_sanitario']),
            'aplicable_a_todos' => fake()->boolean(),
        ];
    }
}
