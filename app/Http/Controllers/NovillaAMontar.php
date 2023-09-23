<?php

namespace App\Http\Controllers;

use App\Http\Resources\NovillaAMontarCollection;
use App\Models\Ganado;
use App\Models\Peso;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NovillaAMontar extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        $novillasAmontar = Peso::whereHas('ganado', function (Builder $query) {
            $query->where('user_id', Auth::id());
        })->where('peso_actual', '>=', 200)->get();
        
        return new NovillaAMontarCollection($novillasAmontar);
    }
}
