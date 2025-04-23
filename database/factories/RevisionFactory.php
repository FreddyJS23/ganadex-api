<?php

namespace Database\Factories;

use App\Models\TipoRevision;
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
            'fecha' => fake()->date()
        ];
    }
}
