<?php

namespace Database\Factories;

use App\Models\Vacuna;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Plan_sanitario>
 */
class Plan_sanitarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $vacuna = Vacuna::all()->random();


        return [
            'fecha_inicio' => fake()->dateTimeThisYear()->format('y-m-d'),
            'fecha_fin' => fake()->dateTimeThisYear()->format('y-m-d'),
            'prox_dosis' => fake()->dateTimeThisYear()->format('y-m-d'),
            'vacuna_id' => 1,
            'vacunados' => rand(1, 100),
            'ganado_vacunado' =>determinar_genero_tipo_ganado($vacuna),
        ];
    }
}
