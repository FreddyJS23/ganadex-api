<?php

namespace App\Http\Controllers;

use App\Http\Resources\TodosPartosCollection;
use App\Models\Ganado;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TodosPartos extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        /* ganado en gestacion pero sin parto */
        $ganadoGestacion= Ganado::doesntHave('toro')
        ->doesntHave('parto')
        ->whereHas('estados',function (Builder $query) {
            $query->whereIn('estado',['gestacion']);
        })
        ->with('estados')
        ->where('hacienda_id', session('hacienda_id'))
        ->get();


        /* vacas con parto, tambien incluye vacas con parto y que esten en gestacion */
        $ganadoParto= Ganado::doesntHave('toro')
        ->has('parto')
        ->with('parto')
        ->withCount('parto')
        ->with('estados')
        ->whereHas('estados',function (Builder $query) {
            $query->whereNotIn('estado',['vendido','fallecido']);
        })
        //ordenenar por estado sano primeros
        ->join('estado_ganado','ganados.id','=','estado_ganado.ganado_id')
        ->orderBy('estado_id')
        ->distinct()
        ->where('hacienda_id', session('hacienda_id'))
        ->get();

        $ganados = $ganadoGestacion->merge($ganadoParto);

        $ganados->transform(
            function (Ganado $ganado) {
              /*   colocar estado segun si esta en gestacion o no, ya que al trae vaca con partos
                algunas pueden estar en gestacion y otras vacias */
                $ganado->estado =  $ganado->estados->contains('estado','gestacion') ? 'Gestacion' : 'Vacia';
                return $ganado;
            }
        );


            return new TodosPartosCollection($ganados);
    }
}
