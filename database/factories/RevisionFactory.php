<?php

namespace Database\Factories;

use App\Models\TipoRevision;
use App\Models\Vacuna;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Revision>
 */
class RevisionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipo_revision_id' => TipoRevision::factory(),
            'tratamiento' =>fake()->boolean() ?  fake()->word() : null,
            'diagnostico' => fake()->boolean() ?  fake()->word() : null,
            'fecha' => fake()->date(),
            'vacuna_id' => fake()->boolean() ? Vacuna::factory() : null,
            'dosis' => fake()->boolean() ? fake()->numberBetween(1, 100) : null,
        ];
    }
}
