<?php

namespace Database\Factories;

use App\Models\Ganado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Parto>
 */
class PartoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'observacion'=>fake()->word(),
            'fecha'=>fake()->date(),
           
            
        ];
    }
}
