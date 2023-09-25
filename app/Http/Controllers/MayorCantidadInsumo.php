<?php

namespace App\Http\Controllers;

use App\Http\Resources\CantidadInsumoResource;
use App\Models\Insumo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MayorCantidadInsumo extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke()
    {
        $menorCantidadInsumo=Insumo::whereBelongsTo(Auth::user())->orderBy('cantidad','desc')->first();

        return response()->json(['mayor_cantidad_insumo'=>new CantidadInsumoResource($menorCantidadInsumo) ]);
    }
}
