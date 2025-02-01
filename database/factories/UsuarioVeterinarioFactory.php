<?php

namespace Database\Factories;

use App\Models\Personal;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\UsuarioVeterinario>
 */
class UsuarioVeterinarioFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory()
            ->create(['usuario'=>'veterinario','password'=>Hash::make('123456')])
            ->assignRole('veterinario'),
        ];
    }
}
