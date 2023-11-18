<?php

namespace App\Http\Controllers;

use App\Http\Resources\TodosServiciosCollection;
use App\Models\Ganado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TodosServicios extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        return new TodosServiciosCollection(Ganado::doesntHave('toro')
        ->has('servicios')
        ->withCount('servicios')
        ->withCount('parto')
        ->where('user_id', Auth::id())->get());

    }
}
