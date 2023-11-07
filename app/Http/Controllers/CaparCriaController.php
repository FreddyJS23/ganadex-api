<?php

namespace App\Http\Controllers;

use App\Http\Resources\CriasPenditeCaparCollection;
use App\Http\Resources\GanadoCollection;
use App\Models\Estado;
use App\Models\Ganado;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CaparCriaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $criasPendienteCapar = Estado::whereHas('ganado', function (Builder $query) {
            $query->where('user_id', Auth::id());
        })->where('estado', 'like', '%-pendiente_capar%')->get();

        return new CriasPenditeCaparCollection($criasPendienteCapar);
    }

    /**
     * Display the specified resource.
     */
    public function capar(Ganado $ganado)
    {
        $ganado->estado->estado=Str::remove('-pendiente_capar',$ganado->estado->estado);
        $ganado->estado->save();
        
        return response();
    }

  
}
