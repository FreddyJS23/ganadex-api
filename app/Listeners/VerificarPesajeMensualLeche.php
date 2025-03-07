<?php

namespace App\Listeners;

use App\Events\CrearSesionHacienda;
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
    public function handle(CrearSesionHacienda $event): void
    {
        $estado = Estado::firstWhere('estado', 'pendiente_pesaje_leche');

        $haciendaId = $event->hacienda->id;

        if (Ganado::where('hacienda_id', $haciendaId)->count() > 0) {
            $vacasSinPesarEsteMes = Ganado::doesntHave('toro')
                ->where('hacienda_id', $haciendaId)
                ->whereRelation('estados', 'estado','!=', 'pendiente_pesaje_leche')
                ->whereRelation('estados', 'estado','!=', 'vendido')
                ->whereRelation('estados', 'estado','!=', 'fallecido')
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
        activity('pesaje mensual leche')
            ->withProperties('evento')
            ->log("Verificado si hay vacas sin pesar en este mes");
    }
}
