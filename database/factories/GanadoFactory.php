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
            'numero' =>fake()->boolean() ? fake()->unique()->numberBetween(0, 32767) : null, // 80% de probabilidad de tener un número
            'origen_id' => fake()->randomElement([1,2]),
            'sexo' => fake()->randomElement(['H']),
            'tipo_id' => 4,
            'fecha_nacimiento' => fake()->date(),
           //al tener un origen externo se asume que debe tener una fecha de ingreso
            'fecha_ingreso' => function(array $attributes) {
                return $attributes['origen_id'] == 2 ? fake()->date() : null;
            },

        ];
    }
}
