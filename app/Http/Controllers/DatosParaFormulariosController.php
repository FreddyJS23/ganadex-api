<?php

namespace App\Http\Controllers;

use App\Http\Resources\CargosPersonalCollection;
use App\Http\Resources\NovillaAMontarCollection;
use App\Http\Resources\VeterinariosDisponiblesCollection;
use App\Models\Cargo;
use App\Models\Personal;
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
    public function cargosPersonalDisponible()
    {
        $cargos = Cargo::get();

        return new CargosPersonalCollection($cargos);
    }
    public function veterinariosDisponibles()
    {
        return new VeterinariosDisponiblesCollection(Personal::select('id','nombre')
        ->where('cargo_id',2)
        ->whereBelongsTo(Auth::user())
        ->get());
    }
}
