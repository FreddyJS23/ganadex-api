<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\RespuestasSeguridad>
 */
class RespuestasSeguridadFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'preguntas_seguridad_id' => random_int(1, 7),
            'respuesta'=>Hash::make(fake()->word()),
        ];
    }
}
