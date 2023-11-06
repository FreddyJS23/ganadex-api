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
        $estadoGanado = Estado::firstWhere('ganado_id', $event->venta->ganado->id);
        $estadoGanado->estado = "vendida";
        $estadoGanado->save();
    }
}
