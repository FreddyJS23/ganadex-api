<?php

namespace App\Http\Controllers;

use App\Http\Resources\TodosPartosCollection;
use App\Models\Ganado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TodosPartos extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
            return new TodosPartosCollection(
                Ganado::doesntHave('toro')
                    ->whereRelation('estados', 'estado','!=', 'fallecido')
                    ->whereRelation('estados', 'estado','!=', 'vendido')
                    ->has('parto')
                    ->withCount('parto')
                    ->where('finca_id', session('finca_id'))
                    ->get()
            );
    }
}
