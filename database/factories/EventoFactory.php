<?php

namespace Database\Factories;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Evento>
 */
class EventoFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'prox_revision' => Carbon::now()->subDay(rand(1, 7))->format('Y-m-d'),
            'prox_parto' => Carbon::now()->subDay(rand(1, 7))->format('Y-m-d'),
            'prox_secado' => Carbon::now()->subDay(rand(1, 7))->format('Y-m-d'),
        ];
    }
}
