<?php

namespace App\Listeners;

use App\Events\CrearSesionHacienda;
use App\Models\Estado;
use App\Models\Hacienda;
use App\Models\Ganado;
use App\Models\Peso;
use App\Models\User;
use DateTime;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;

class VerificarVacasAptaParaServicio
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
        $estado = Estado::firstWhere('estado', 'pendiente_servicio');
        $haciendaId = $event->hacienda->id;

        if (Ganado::where('hacienda_id', $haciendaId)->count() > 0) {
            $vacasAptasParaServicio = Ganado::doesntHave('toro')
                ->doesntHave('ganadoDescarte')
                ->doesntHave('fallecimiento')
                ->doesntHave('venta')
                ->whereHas('estados',function (Builder $query) {
                    $query->whereNotIn('estado',['vendido','fallecido','pendiente_servicio','gestacion']);
                })
                ->where('hacienda_id', $haciendaId)
                ->whereHas(
                    'peso',
                    function (Builder $query) {
                        $query->where('peso_actual', '>=', session('peso_servicio'));
                    }
                )
            ->get();


            if(  $vacasAptasParaServicio->count() > 0){
                foreach ($vacasAptasParaServicio as $vacaAptaParaServicio) {

                    //si ya tiene el estado pendiente servicio no se hace nada
                    //sin esto los estados pendientes de servicio se acumulan
                    if($vacaAptaParaServicio->estados->contains('estado', 'pendiente_servicio')) continue;
                    $vacaAptaParaServicio->estados()->attach($estado->id);
                }
            };
        }
        activity('servicio')
            ->withProperties('evento')
            ->log("Verificado si hay vacas aptas para un  servicio");
    }
}
