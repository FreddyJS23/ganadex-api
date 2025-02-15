<?php

namespace App\Listeners;

use App\Events\PesajeLecheHecho;
use App\Models\Estado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class EstadoPosPesajeMensualLeche
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
    public function handle(PesajeLecheHecho $event): void
    {
        $estado = Estado::firstWhere('estado', 'pendiente_pesaje_leche');

        $event->ganado->estados()->detach($estado->id);

        $numero = $event->ganado->numero;
        activity("pesaje de leche")
            ->withProperties('evento')
            ->log("Animal $numero ya no esta pendiente de pesaje de leche");
    }
}
