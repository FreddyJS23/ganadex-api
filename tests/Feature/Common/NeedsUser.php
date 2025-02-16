<?php

namespace Tests\Feature\Common;

use App\Models\User;

trait NeedsUser
{
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user  = User::factory()->hasConfiguracion()->create();
        $this->user->assignRole('admin');
    }

    private function getSessionInitializationArray(): array
    {
        return [
            'peso_servicio' => $this->user->configuracion->peso_servicio,
            'dias_Evento_notificacion' => $this->user->configuracion->dias_evento_notificacion,
            'dias_diferencia_vacuna' => $this->user->configuracion->dias_diferencia_vacuna
        ];
    }
}
