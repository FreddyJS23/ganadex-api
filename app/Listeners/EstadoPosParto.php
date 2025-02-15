<?php

namespace App\Listeners;

use App\Events\PartoHecho;
use App\Models\Estado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class EstadoPosParto
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
        $estado = Estado::whereIn('estado', ['sano','lactancia'])->get();

        $event->parto->ganado->estados()->sync($estado);

        $numero = $event->parto->ganado->numero;
        activity("parto")
            ->withProperties('evento')
            ->log("Animal $numero ahora tiene estado de lactancia");
    }
}
