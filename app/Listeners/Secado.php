<?php

namespace App\Listeners;

use App\Events\RevisionPrenada;
use App\Models\Estado;
use App\Models\Evento;
use DateInterval;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class Secado
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
        //desactivar secado si no hay parto reciente
        if ($event->revision->ganado->partoReciente == null) {
            return ;
        }

        $eventoGanado = Evento::firstWhere('ganado_id', $event->revision->ganado->id);
        $fechaServicio = new DateTime($event->revision->ganado->servicioReciente->fecha);
        $fechaSecado = $fechaServicio->add(new DateInterval('P210D'))->format('Y-m-d');
        $eventoGanado->prox_secado = $fechaSecado;
        $eventoGanado->save();

        $estado = Estado::firstWhere('estado', 'pendiente_secar');

        $event->revision->ganado->estados()->attach($estado->id);

        $numero = $event->revision->ganado->numero;
        activity('revision')
            ->withProperties('evento')
            ->log("Animal $numero ahora tiene fecha de secado");
    }
}
