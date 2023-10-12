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
            'peso_nacimiento'=>fake()->numerify('###KG'),
            'peso_destete'=>fake()->numerify('###KG'),
            'peso_2year'=>fake()->numerify('###KG'),
            'peso_actual'=>fake()->numerify('###KG'),
        ];
    }
}
