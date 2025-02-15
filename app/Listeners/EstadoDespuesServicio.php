<?php

namespace App\Listeners;

use App\Events\ServicioHecho;
use App\Models\Estado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class EstadoDespuesServicio
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
        $estadoPendienteServico = Estado::where('estado', ['pendiente_servicio'])->first()->id;

        //eliminar estado pendiente de servicio
        $event->servicio->ganado->estados()->detach($estadoPendienteServico);
    }
}
