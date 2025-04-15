<?php

namespace App\Http\Controllers;

use App\Http\Resources\TodosRevisionesCollection;
use App\Models\Ganado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TodosRevisiones extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        $ganado=Ganado::doesntHave('toro')

        ->doesntHave('ganadoDescarte')
        ->withCount('revision')
        ->with('estados')
         //ordenenar por estado sano primeros
        ->join('estado_ganado','ganados.id','=','estado_ganado.ganado_id')
        ->orderBy('estado_id')
        ->where('hacienda_id', session('hacienda_id'))
        ->distinct()
        ->get();

        $ganado->transform(
            function (Ganado $ganado) {
                $ganado->pendiente =  $ganado->estados->contains('estado','pendiente_revision');
                $ganado->estado = $ganado->estados->first();
                return $ganado;
            }
        );

        return new TodosRevisionesCollection($ganado);
    }
}
