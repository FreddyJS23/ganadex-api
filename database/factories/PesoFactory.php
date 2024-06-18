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
            'peso_nacimiento'=>fake()->numberBetween(0,32600),
            'peso_destete'=>fake()->numberBetween(0,32600),
            'peso_2year'=>fake()->numberBetween(0,32600),
            'peso_actual'=>fake()->numberBetween(0,32600),
        ];
    }
}
