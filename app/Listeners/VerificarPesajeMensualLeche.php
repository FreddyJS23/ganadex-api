<?php

namespace App\Listeners;

use App\Events\CrearSesionHacienda;
use App\Models\Estado;
use App\Models\Ganado;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
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
    public function handle(CrearSesionHacienda $event): void
    {
        $estado = Estado::firstWhere('estado', 'pendiente_pesaje_leche');

        $haciendaId = $event->hacienda->id;

        if (Ganado::where('hacienda_id', $haciendaId)->count() > 0) {
            $vacasSinPesarEsteMes = Ganado::doesntHave('toro')
                ->where('hacienda_id', $haciendaId)
                ->whereHas('estados',function (Builder $query) {
                    $query->whereNotIn('estado',['vendido','fallecido','pendiente_pesaje_leche'])
                    ->whereIn('estado',['lactancia']);
                })
                ->whereHas(
                    'pesajes_leche',
                    function (Builder $query) {
                        $query->whereMonth('fecha', '!=', now()->month)
                            ->whereYear('fecha', now()->year);
                    }
                )
                ->get();

            foreach ($vacasSinPesarEsteMes as $vacaSinPesarEsteMes) {
                
                //si ya tiene el estado pendiente pesaje de leche no se hace nada
                //sin esto los estados pendientes de pesaje de leche se acumulan
                if($vacaSinPesarEsteMes->estados->contains('estado', 'pendiente_pesaje_leche')) return;
                
                $vacaSinPesarEsteMes->estados()->attach($estado->id);
            }
        }
        activity('pesaje mensual leche')
            ->withProperties('evento')
            ->log("Verificado si hay vacas sin pesar en este mes");
    }
}
