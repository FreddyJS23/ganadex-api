<?php

namespace App\Listeners;

use App\Events\RevisionPrenada;
use App\Models\Estado;
use App\Models\Evento;
use DateInterval;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class EstadoGestacion
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
    public function handle(RevisionPrenada $event): void
    {
        $eventoGanado = Evento::firstWhere('ganado_id', $event->revision->ganado->id);
        $fechaServicio = new DateTime($event->revision->ganado->servicioReciente->fecha);
        $fechaParto = $fechaServicio->add(new DateInterval('P270D'))->format('Y-m-d');
        $eventoGanado->prox_parto = $fechaParto;
        $eventoGanado->save();

        $estado = Estado::whereIn('estado', ['sano','gestacion'])->get();

        $event->revision->ganado->estados()->sync($estado);

        $numero = $event->revision->ganado->numero;
        activity("revision")
            ->withProperties('evento')
            ->log("Animal $numero ahora esta en gestacion");
    }
}
