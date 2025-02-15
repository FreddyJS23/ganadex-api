<?php

namespace App\Listeners;

use App\Events\VentaGanado;
use App\Models\Estado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class EstadoPosVenta
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
    public function handle(VentaGanado $event): void
    {


        $estado = Estado::firstWhere('estado', 'vendido');

        $numero = $event->venta->ganado->numero;
        activity('venta')
            ->withProperties('evento')
            ->log("Animal $numero ha sido vendido");

        $event->venta->ganado->estados()->sync($estado->id);
    }
}
