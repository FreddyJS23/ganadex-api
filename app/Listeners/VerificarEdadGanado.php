<?php

namespace App\Listeners;

use App\Models\Ganado;
use App\Models\User;
use DateTime;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Auth;

class VerificarEdadGanado
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
        $incializarFecha = new DateTime();
        $fechaActual = $incializarFecha->format('Y-m-d');
        //consulta sql, la diferencia seran los dias que le saca la fecha de nacimiento con la fecha actual
        $sentenciaSqlDiferenciaDias = "DATEDIFF('$fechaActual',fecha_nacimiento) as diferencia";
        $usuarioId = $event->user->getAuthIdentifier();;

        if (Ganado::where('user_id', $usuarioId)->count() > 0) {
            $becerros = Ganado::where('user_id', $usuarioId)
                ->where('tipo_id', 1)
                ->select('tipo_id')
                ->selectRaw($sentenciaSqlDiferenciaDias)
                ->having('diferencia', '>=',  365)
                ->having('diferencia', '<',  729)
                ->get();

            $becerros->toQuery()->update(['tipo_id' => 2]);

            $mautes = Ganado::where('user_id', $usuarioId)
                ->where('tipo_id', 2)
                ->select('tipo_id')
                ->selectRaw($sentenciaSqlDiferenciaDias)
                ->having('diferencia', '>=', 730)
                ->get();

            $mautes->toQuery()->update(['tipo_id' => 3]);
        }
    }
}
