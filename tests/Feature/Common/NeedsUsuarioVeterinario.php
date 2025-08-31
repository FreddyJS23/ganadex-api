<?php

namespace Tests\Feature\Common;

use App\Models\Personal;
use App\Models\User;
use App\Models\UsuarioVeterinario;

trait NeedsUsuarioVeterinario
{

    private User $userVeterinario;


    protected function setUp(): void
    {
        $this->userVeterinario
        = User::factory()
        ->create(['usuario' => 'veterinario']);

        $this->userVeterinario->assignRole('veterinario');

        UsuarioVeterinario::factory()
        ->for(Personal::factory()->hasAttached($this->hacienda)->for($this->user)->create(['nombre'=>'usuarioVeterinario','cargo_id' => 2]), 'veterinario')
        ->create(['admin_id' => $this->user->id,
        'user_id' => $this->userVeterinario->id]);
    }

}
