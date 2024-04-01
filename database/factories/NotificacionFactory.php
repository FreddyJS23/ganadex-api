<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Notificacion>
 */
class NotificacionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo_id' => fake()->randomElement([1,2,3]),
            'leido' => fake()->randomElement([true, false]),
            'dias_para_evento'=>fake()->numberBetween(-6,7)
        ];
    }
}
