<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGanadoRequest;
use App\Http\Requests\StoreNumeroCriaRequest;
use App\Http\Requests\UpdateGanadoRequest;
use App\Http\Resources\CriasPendienteNumeracionCollection;
use App\Models\Estado;
use App\Models\Ganado;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class AsignarNumeroCriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $criasPendienteNumeracion = Estado::whereHas('ganado', function (Builder $query) {
            $query->where('user_id', Auth::id());
        })->where('estado', 'like', '%-pendiente_numeracion%')->get();

        return new CriasPendienteNumeracionCollection($criasPendienteNumeracion);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreNumeroCriaRequest $request,Ganado $ganado)
    {
        $ganado->estado->estado = Str::remove('-pendiente_numeracion', $ganado->estado->estado);
        $ganado->numero=$request->input('numero');
        $ganado->push();

        return response();
    }

   
}
