<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Peso>
 */
class PesoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'peso_nacimiento' => fake()->numberBetween(0,60),
            'peso_destete' => fake()->numberBetween(60,100 ),
            'peso_2year' => fake()->numberBetween(400,500 ),
            'peso_actual' => fake()->numberBetween(500, 600),
        ];
    }
}
