<?php

namespace App\Listeners;

use App\Events\CrearSesionHacienda;
use App\Models\Evento;
use App\Models\Ganado;
use App\Models\Notificacion;
use App\Models\TiposNotifiacion;
use App\Models\TiposNotificacion;
use Illuminate\Auth\Events\Login;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class GenerarNotificaciones
{
    /* se utiliza este scope ya que el evento
    que es login tiene varios listener por ende
    llama varias veces este archivo, si se declaran estas funciones
    dentro del handle, dara error que la funcion ya se ha creado anteriormente */
    private function CrearNotificaion($tipo, $userId, $ganadoId, $diferenciaDiasEvento)
    {
        //Datos en la tabla tipos notificacion
        $columnaTipoNotificacion = ['revision' => 1, 'parto' => 2, 'secado' => 3,];

        Notificacion::updateOrCreate(
            [
                'ganado_id' => $ganadoId,
                'tipo_id' => $columnaTipoNotificacion["$tipo"],
                'hacienda_id' => session('hacienda_id')
            ],
            [
                'dias_para_evento' => $diferenciaDiasEvento,
            ]
        );
    }

    //Consultar si un evento esta cercano a los 7 dias
    private function VerificarEventoCercano($evento, $usuarioId, $haciendaId, $fechaActual)
    {
        //eliminar sufijo de la columna eventos
        $tipoEvento = Str::of($evento)->after('prox_');
        $nombreColumna = "dias_para_" . $tipoEvento;

        $eventosProximos = Evento::whereRelation('ganado', 'hacienda_id', $haciendaId)
            ->select('id', 'ganado_id')
            ->selectRaw("DATEDIFF($evento,'$fechaActual') AS $nombreColumna")
            ->having("dias_para_$tipoEvento", '<=', session('dias_evento_notificacion'))->get();

        //interactuar con una coleccion de eventos para crear notificacion
        if ($eventosProximos->count() > 0) {
            foreach ($eventosProximos as $eventoProximo) {
                $this->CrearNotificaion($tipoEvento, $usuarioId, $eventoProximo->ganado_id, $eventoProximo["$nombreColumna"]);
            }
        }
    }

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
        $haciendaId = $event->hacienda->id;
        $fechaActual = now()->format('Y-m-d');
        if (Ganado::where('hacienda_id', $haciendaId)->count() > 0) {
            //extraer nombres de columnas de la tabla
            $columnasTablaEvento = Schema::getColumnListing('eventos');
            //intercambiar key=>valor por valor=>key exeptuando
            $columnasTablaEvento = array_flip($columnasTablaEvento);
            //exeptuar columnas inncesarias
            $columnasTablaEvento = (array) Arr::except($columnasTablaEvento, ['id', 'ganado_id', 'created_at', 'updated_at']);

            //iterar columnas
            foreach ($columnasTablaEvento as $columna => $key) {
                $this->VerificarEventoCercano($columna, $event->hacienda->user_id, $haciendaId, $fechaActual);
            }


            activity("notificaciones")
                ->withProperties('evento')
                ->log('Se han generado las notificaciones');
        }
    }
}
