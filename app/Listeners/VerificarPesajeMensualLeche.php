<?php

namespace App\Listeners;

use App\Models\Estado;
use App\Models\Ganado;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;

use Illuminate\Queue\InteractsWithQueue;

class VerificarPesajeMensualLeche
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
    public function handle(Login $event): void
    {
        $estado = Estado::firstWhere('estado', 'pendiente_pesaje_leche');

        //no se obtiene la finca de la sesion ya que se esta logeando manualmente
        $fincaId = $event->user->fincas->first()->id;

        if (Ganado::where('finca_id', $fincaId)->count() > 0) {

            $vacasSinPesarEsteMes = Ganado::doesntHave('toro')
                ->where('finca_id', $fincaId)
                ->whereHas(
                    'pesajes_leche',
                    function (Builder $query) {
                        $query->whereMonth('fecha', '!=', now()->month)
                            ->whereYear('fecha', now()->year);
                    }
                )
                ->get();


            foreach ($vacasSinPesarEsteMes as $vacaSinPesarEsteMes) {
                $vacaSinPesarEsteMes->estados()->attach($estado->id);
            }
        }
    }
}
