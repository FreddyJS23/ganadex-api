<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Ganado>
 */
class GanadoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->firstName(),
            'numero' => fake()->unique()->numberBetween($int = 0, $int = 32767),
            'origen' => fake()->randomElement(['local','externo']),
            'sexo' => fake()->randomElement(['H','M']),
            'tipo_id' => 4,
            'fecha_nacimiento' => fake()->date(),

        ];
    }
}
