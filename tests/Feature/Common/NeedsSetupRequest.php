<?php

namespace Tests\Feature\Common;

use Illuminate\Foundation\Testing\RefreshDatabase;

trait NeedsSetupRequest
{
    use RefreshDatabase,
        NeedsHacienda,
        NeedsUsuarioVeterinario {
            NeedsHacienda::setUp insteadof NeedsUsuarioVeterinario;
            NeedsUsuarioVeterinario::setUp insteadof NeedsHacienda;
            NeedsHacienda::setUp as needsHaciendaSetUp;
            NeedsUsuarioVeterinario::setUp as needsUsuarioVeterinarioSetUp;
            NeedsHacienda::getSessionInitializationArray as needsHaciendaGetSessionInitializationArray;
        }

    protected function setUp(): void
    {
        parent::setUp();
        $this->needsHaciendaSetUp();
        $this->needsUsuarioVeterinarioSetUp();
    }

/**
 * Este método se utiliza para establecer la sesión de un usuario en función del parámetro $userIsVeterinario
 * @param bool $userIsVeterinario, Indica si la sesión debe ser de un usuario veterinario o no
 * @return static
  */
    private function setUpRequest(bool $userIsVeterinario = false): static
    {
        if ($userIsVeterinario) {
            $this
            ->actingAs($this->userVeterinario)
            ->withSession($this->getSessionInitializationArray());
        } else {
            $this
            ->actingAs($this->user)
            ->withSession($this->getSessionInitializationArray());
        }


        return $this;
    }
}
