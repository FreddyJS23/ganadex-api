<?php

namespace App\Listeners;

use App\Models\Evento;
use App\Events\ServicioHecho;
use DateInterval;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RevisionServicio
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
    public function handle(ServicioHecho $event): void
    {
        $eventoGanado = Evento::firstWhere('ganado_id', $event->servicio->ganado->id);
        $fechaServicio = new DateTime($event->servicio->fecha);
        $proxRevision = $fechaServicio->add(new DateInterval('P40D'))->format('Y-m-d');
        $eventoGanado->prox_revision = $proxRevision;
        $eventoGanado->save();

        $numero = $event->servicio->ganado->numero;
        activity("servicio")
            ->withProperties('evento')
            ->log("Animal $numero tiene una proxima revision despues del servicio");
    }
}
