<?php

namespace App\Http\Controllers;

use App\Http\Resources\TodosPesajeLecheCollection;
use App\Models\Ganado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TodosPesajeLeche extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        return new TodosPesajeLecheCollection(
            Ganado::whereRelation('estados', 'estado','lactancia')
                ->whereRelation('estados', 'estado','!=', 'fallecido')
                ->whereRelation('estados', 'estado','!=', 'vendido')
                ->where('hacienda_id', session('hacienda_id'))
                ->get()
        );
    }
}
