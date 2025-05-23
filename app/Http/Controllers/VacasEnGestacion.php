<?php

namespace App\Http\Controllers;

use App\Models\Ganado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VacasEnGestacion extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
           $totalVacasEnGestacion = Ganado::where('hacienda_id', session('hacienda_id'))
               ->whereRelation('estados', 'estado', 'gestacion')
               ->count();

        return response()->json(['vacas_en_gestacion' => $totalVacasEnGestacion], 200);
    }
}
