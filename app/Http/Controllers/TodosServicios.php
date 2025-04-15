<?php

namespace App\Http\Controllers;

use App\Http\Resources\TodosServiciosCollection;
use App\Models\Ganado;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TodosServicios extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {

        $ganados = Ganado::doesntHave('toro')
            ->doesntHave('ganadoDescarte')
            //peso minimo para servicio
            ->whereHas(
                'peso',
                function (Builder $query) {
                    $query->where('peso_actual', '>=', session('peso_servicio'));
                }
            )
            ->with('servicios')
            ->with('estados')
            //ordenenar por estado sano primeros
            ->join('estado_ganado','ganados.id','=','estado_ganado.ganado_id')
            ->distinct()
            ->orderBy('estado_id')
            ->with(
                [
                'parto' => function (Builder $query) {
                    $query->orderBy('fecha', 'desc');
                },
                ]
            )
            ->withCount('servicios')
            ->where('hacienda_id', session('hacienda_id'))->get();
        $ganados->transform(
            function (Ganado $ganado) {

                $ganado->efectividad = null;
                //comprobar si esta pendiente de un servicio, para poder filtrar
                $ganado->pendiente = $ganado->estados->contains('estado','pendiente_servicio');

                //estado
                $ganado->estado = $ganado->estados->first();

                /*efectividad respecto a cuantos servicios fueron realizados para que la vaca quede prenada */
                $efectividad = fn (int $resultadoAlcanzado) => round(1 / $resultadoAlcanzado * 100, 2);

                if ($ganado->parto->count() == 1) {
                    $ganado->load('servicios');

                    $ganado->efectividad = $ganado->servicios->count() >= 1 ? $efectividad($ganado->servicios->count()) : null;
                } elseif ($ganado->parto->count() >= 2) {
                    $fechaInicio = $ganado->parto[1]->fecha;
                    $fechaFin = $ganado->parto[0]->fecha;

                    $ganado->fechaInicio = $fechaInicio;
                    $ganado->fechaFin = $fechaFin;

                    $ganado->load(
                        ['servicios' => function (Builder $query) use ($fechaInicio, $fechaFin) {
                            $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                        }]
                    );

                    $ganado->efectividad = $ganado->servicios->count() >= 1 ?  $efectividad($ganado->servicios->count()) : null;
                }
                return $ganado;
            }
        );


        return new TodosServiciosCollection($ganados);
    }
}
