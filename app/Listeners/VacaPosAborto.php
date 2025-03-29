<?php

namespace App\Listeners;

use App\Events\RevisionAborto;
use App\Models\Evento;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class VacaPosAborto
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
    public function handle(RevisionAborto $event): void
    {
        $eventoGanado = Evento::firstWhere('ganado_id', $event->revision->ganado->id);
        /* anular la fecha de parto y secado */
        $eventoGanado->prox_parto = null;
        $eventoGanado->prox_secado = null;
        $eventoGanado->save();

        //id sacado del seeder estado
        $estadoGestacionId=3;
        //id sacado del seeder estado
        $estadoPendienteSecarId=8;
        /* anular estado gestacion */
        $event->revision->ganado->estados()->detach([$estadoGestacionId]);
        /* anular estado pendiente secar */
        $event->revision->ganado->estados()->detach([$estadoPendienteSecarId]);

        $numero = $event->revision->ganado->numero;
        activity("revision")
        ->log("Vaca $numero ha tenido un aborto");

    }
}
