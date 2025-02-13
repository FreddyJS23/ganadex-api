<?php

namespace App\Listeners;

use App\Events\CrearSesionFinca;
use App\Models\Estado;
use App\Models\Finca;
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
    public function handle(CrearSesionFinca $event): void
    {
        $estado = Estado::firstWhere('estado', 'pendiente_servicio');
        $fincaId = $event->finca->id;

        if (Ganado::where('finca_id', $fincaId)->count() > 0) {

            $vacasAptasParaServicio = Ganado::doesntHave('toro')
            ->doesntHave('ganadoDescarte')
            ->doesntHave('fallecimiento')
            ->doesntHave('venta')
            ->whereRelation('estados','estado','sano')
            ->where('finca_id', $fincaId)
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
