<?php

namespace App\Listeners;

use App\Events\NaceMacho;
use App\Models\Estado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class CaparBecerro
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
    public function handle(NaceMacho $event): void
    {
        $estadoGanado=Estado::firstWhere('ganado_id',$event->ganado->id);
        $estadoGanado->estado=$estadoGanado->estado . "-pendiente_capar";
        $estadoGanado->save();
    }
}
