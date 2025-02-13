<?php

namespace App\Listeners;

use App\Events\PartoHecho;
use App\Models\Evento;
use DateInterval;
use DateTime;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class RevisionPosParto
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
    public function handle(PartoHecho $event): void
    {
        $eventoGanado=Evento::firstWhere('ganado_id',$event->parto->ganado->id);
        $fechaParto=new DateTime($event->parto->fecha);
        $proxRevision=$fechaParto->add(new DateInterval('P30D'))->format('Y-m-d');
        $eventoGanado->prox_revision=$proxRevision;
        $eventoGanado->save();

        $numero = $event->parto->ganado->numero;
        activity("parto")
        ->withProperties('evento')
        ->log("Animal $numero tiene una fecha de revision despues del parto");
    }
}
