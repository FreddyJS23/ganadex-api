<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PajuelaToro>
 */
class PajuelaToroFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'codigo' => fake()->regexify('[A-Z]{5}[0-4]{3}'),
            'descripcion' => fake()->sentence(),
            'fecha' => fake()->dateTimeThisYear()->format('Y-m-d'),
        ];
    }
}
