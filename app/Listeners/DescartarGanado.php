<?php

namespace App\Listeners;

use App\Events\RevisionDescarte;
use App\Http\Controllers\GanadoController;
use App\Http\Controllers\GanadoDescarteController;
use App\Models\GanadoDescarte;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;

class DescartarGanado
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
    public function handle(RevisionDescarte $event): void
    {
        $ganadoDescarte = new GanadoDescarte();
        $ganadoDescarte->ganado_id = $event->revision->ganado_id;
        $ganadoDescarte->finca_id = session('finca_id');
        $ganadoDescarte->save();
    }
}
