<?php

namespace App\Listeners;

use App\Events\FallecimientoGanado;
use App\Models\Estado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class EstadoPosFallecimiento
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(FallecimientoGanado $event): void
    {
        $estado = Estado::firstWhere('estado', 'fallecido');
        $event->ganado->estados()->sync($estado->id);

        $numero = $event->ganado->numero;
        activity("fallecimiento")
            ->withProperties('evento')
            ->log("Estado fallecimiento animal $numero");
    }
}
