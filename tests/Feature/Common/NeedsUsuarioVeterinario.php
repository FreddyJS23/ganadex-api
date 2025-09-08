<?php

namespace Tests\Feature\Common;

use App\Models\Personal;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use Illuminate\Support\Facades\Hash;

trait NeedsUsuarioVeterinario
{

    private User $userVeterinario;
    private UsuarioVeterinario $infoUsuarioVeterinario;


    protected function setUp(): void
    {
        $this->userVeterinario
        = User::factory()
        ->create(['usuario' => 'veterinario', 'password' => Hash::make('veterinario')]);

        $this->userVeterinario->assignRole('veterinario');

        $this->infoUsuarioVeterinario = UsuarioVeterinario::factory()
        ->for(Personal::factory()->hasAttached($this->hacienda)->for($this->user)->create(['nombre'=>'usuarioVeterinario','cargo_id' => 2]), 'veterinario')
        ->create(['admin_id' => $this->user->id,
        'user_id' => $this->userVeterinario->id]);
    }

}
