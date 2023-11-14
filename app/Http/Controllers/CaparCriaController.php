<?php

namespace App\Http\Controllers;

use App\Http\Resources\CriasPenditeCaparCollection;
use App\Models\Estado;
use App\Models\Ganado;

use Illuminate\Support\Facades\Auth;


class CaparCriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $criasPendienteCapar = Ganado::whereBelongsTo(Auth::user())
        ->whereRelation('estados','estado','pendiente_capar')
        ->get();

        return new CriasPenditeCaparCollection($criasPendienteCapar);
    }

    /**
     * Display the specified resource.
     */
    public function capar(Ganado $ganado)
    {
        $estado=Estado::firstWhere('estado','pendiente_capar');
        $ganado->estados()->detach($estado->id);
        
        return response()->json();
    }

  
}
