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
        $estadoGanado=Estado::firstWhere('ganado_id',$event->parto->ganado->id);
        $estadoGanado->estado="sana-lactancia";
        $estadoGanado->save();
    }
}
