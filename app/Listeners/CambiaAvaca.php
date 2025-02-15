<?php

namespace App\Listeners;

use App\Events\PartoHecho;
use App\Models\Ganado;
use App\Models\GanadoTipo;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CambiaAvaca
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
        $novilla = Ganado::find($event->parto->ganado->id);
        $novilla->tipo_id = GanadoTipo::where('tipo', 'adulto')->first()->id;
        $novilla->save();

        $numero = $event->parto->ganado->numero;
        activity("parto")
            ->withProperties('evento')
            ->log("Animal $numero cambia a vaca despues del parto");
    }
}
