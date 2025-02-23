<?php

namespace App\Listeners;

use App\Events\CrearSesionFinca;
use App\Models\Finca;
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
    public function handle(CrearSesionFinca $event): void
    {
        $incializarFecha = new DateTime();
        $fechaActual = $incializarFecha->format('Y-m-d');
        //consulta sql, la diferencia seran los dias que le saca la fecha de nacimiento con la fecha actual
        $sentenciaSqlDiferenciaDias = "DATEDIFF('$fechaActual',fecha_nacimiento) as diferencia";


        $fincaId = $event->finca->id;

        if (Ganado::where('finca_id', $fincaId)->count() > 0) {
            $becerros = Ganado::where('finca_id', $fincaId)
                ->where('tipo_id', 1)
                ->select('id')
                ->selectRaw($sentenciaSqlDiferenciaDias)
                ->having('diferencia', '>=', 365)
                ->having('diferencia', '<', 729)
                ->get();

                //extraer ids de todos los becerros encontrados
                $becerrosIds = $becerros->modelKeys();

                if( count($becerrosIds) > 0)
                    Ganado::whereIn('id',$becerrosIds)->update(['tipo_id' => 2]);


            $mautes = Ganado::where('finca_id', $fincaId)
                ->where('tipo_id', 2)
                ->select('id')
                ->selectRaw($sentenciaSqlDiferenciaDias)
                ->having('diferencia', '>=', 730)
                ->get();

            $mautesIds = $mautes->modelKeys();

            if( count($mautesIds) > 0)
                Ganado::whereIn('id',$mautesIds)->update(['tipo_id' => 3]);

        }

        activity('edad ganado')
            ->withProperties('evento')
            ->log("Verificada edad de todos los animales");
    }
}
