<?php

namespace App\Http\Controllers;

use App\Http\Resources\TopVacasProductorasCollection;
use App\Models\Leche;
use DateTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TopVacasProductoras extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        $fechaActual=new DateTime();
        $mesActual=$fechaActual->format('m');
        $topVacasProductoras = Leche::whereHas('ganado', function (Builder $query) {
            $query->where('user_id', Auth::id());
        })->orderBy('peso_leche','desc')->whereMonth('fecha',$mesActual)->limit(3)->get();

        return new TopVacasProductorasCollection($topVacasProductoras);
    }
}
