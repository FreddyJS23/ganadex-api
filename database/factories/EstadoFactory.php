<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Estado>
 */
class EstadoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'estado'=>fake()->randomElement(['sano','fallecido','sano-gestacion','sano-pendiente_revision','sano-pendiente_servicio']),
            'fecha_defuncion'=>fake()->optional()->date(),
            'causa_defuncion'=>fake()->optional()->sentence(), 
        ];
    }
}
