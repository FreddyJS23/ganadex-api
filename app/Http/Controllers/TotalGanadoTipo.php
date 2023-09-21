<?php

namespace App\Http\Controllers;

use App\Http\Resources\TotalGanadoTipoCollection;
use App\Models\GanadoTipo;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class TotalGanadoTipo extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        $TotalGanadoPorTipos = GanadoTipo::withCount(['ganado' => function (Builder $query) {
            $query->where('user_id', Auth::id());
        }]);

        return  new TotalGanadoTipoCollection($TotalGanadoPorTipos->get());
    }
}
