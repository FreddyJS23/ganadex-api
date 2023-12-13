<?php

namespace App\Http\Controllers;

use App\Http\Resources\NovillaAMontarCollection;
use App\Models\Peso;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DatosParaFormulariosController extends Controller
{
    public function novillasParaMontar()
    {
        $novillasAmontar = Peso::whereHas('ganado', function (Builder $query) {
            $query->where('user_id', Auth::id());
        })->where('peso_actual', '>=', 330)->get();

        return new NovillaAMontarCollection($novillasAmontar);
    }
}
