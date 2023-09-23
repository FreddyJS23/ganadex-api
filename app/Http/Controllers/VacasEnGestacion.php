<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class VacasEnGestacion extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $totalVacasEnGestacion = Estado::whereHas('ganado', function (Builder $query) {
            $query->where('user_id', Auth::id());
        })->where('estado', 'like', '%gestacion%')->count();
        
       return response()->json(['vacas_en_gestacion'=>$totalVacasEnGestacion],200);
    }
}
