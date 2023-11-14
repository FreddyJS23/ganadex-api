<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreNumeroCriaRequest;
use App\Http\Resources\CriasPendienteNumeracionCollection;
use App\Models\Estado;
use App\Models\Ganado;
use Illuminate\Support\Facades\Auth;


class AsignarNumeroCriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {       
         $criasPendienteNumeracion = Ganado::whereBelongsTo(Auth::user())
         ->whereRelation('estados','estado','pendiente_numeracion')
        ->get(); 

        return new CriasPendienteNumeracionCollection($criasPendienteNumeracion);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNumeroCriaRequest $request,Ganado $ganado)
    {
        $ganado->numero=$request->input('numero');
        $ganado->save();
        
        $estado=Estado::firstWhere('estado','pendiente_numeracion');

        $ganado->estados()->detach($estado->id);
        
        return response()->json();
    }

   
}
