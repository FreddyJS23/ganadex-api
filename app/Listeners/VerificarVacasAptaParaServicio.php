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
                ->whereRelation('estados', 'estado', 'sano')
                ->whereRelation('estados', 'estado','!=', 'gestacion')
                ->whereRelation('estados', 'estado','!=', 'pendiente_servicio')
                ->whereRelation('estados', 'estado','!=', 'vendido')
                ->whereRelation('estados', 'estado','!=', 'fallecido')
                ->where('hacienda_id', $haciendaId)
                ->whereHas(
                    'peso',
                    function (Builder $query) {
                        $query->where('peso_actual', '>=', session('peso_servicio'));
                    }
                )
            ->get();

            foreach ($vacasAptasParaServicio as $vacaAptaParaServicio) {
                $vacaAptaParaServicio->estados()->attach($estado->id);
            }
        }
        activity('servicio')
            ->withProperties('evento')
            ->log("Verificado si hay vacas aptas para un  servicio");
    }
}
