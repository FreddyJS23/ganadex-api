<?php

namespace Database\Factories;

use App\Models\CausasFallecimiento;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Fallecimiento>
 */
class FallecimientoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'causas_fallecimiento_id' => CausasFallecimiento::factory(),
            'descripcion' => fake()->sentence(),
            'fecha' => fake()->date()
        ];
    }
}
