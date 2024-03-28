<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Personal>
 */
class PersonalFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ci' => fake()->unique()->randomNumber(8),
            'nombre' => fake()->firstName(),
            'apellido' => fake()->lastName(),
            'fecha_nacimiento' => fake()->date(),
            'telefono' => fake()->numerify('0275-#######'),
            'cargo_id'=>fake()->randomElement([1,2])
            /* 'sueldo' => fake()->numberBetween($int=1,$max=100), */
        ];
    }
}
