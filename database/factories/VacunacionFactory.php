<?php

namespace Database\Factories;

use App\Models\Vacuna;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Vacunacion>
 */
class VacunacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'fecha' => fake()->dateTimeThisYear()->format('y-m-d'),
            'prox_dosis' => fake()->dateTimeThisYear()->format('y-m-d'),
            'vacuna_id' => Vacuna::all()->random()->id,
        ];
    }
}
