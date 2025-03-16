<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Leche>
 */
class LecheFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'peso_leche' => fake()->numberBetween(10, 40),
            'fecha' => fake()->dateTimeThisYear()->format('y-m-d')
        ];
    }
}
