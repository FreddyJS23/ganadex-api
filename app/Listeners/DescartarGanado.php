<?php

namespace App\Listeners;

use App\Events\RevisionDescarte;
use App\Http\Controllers\GanadoController;
use App\Http\Controllers\GanadoDescarteController;
use App\Models\Estado;
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
        $estado = Estado::firstWhere('estado', 'sano');
        $event->revision->ganado->estados()->sync($estado->id);
        $ganadoDescarte->hacienda_id = session('hacienda_id');
        $ganadoDescarte->save();

        $numero = $event->revision->ganado->numero;
        activity("revision")
        ->log("Animal $numero descartado");
    }
}
