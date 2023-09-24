<?php

namespace App\Http\Controllers;

use App\Models\Estado;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TotalGanadoPendienteRevision extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
        $totalGanadoPendienteRevision=Estado::whereHas('ganado', function (Builder $query) {
            $query->where('user_id', Auth::id());
        })->where('estado', 'like', '%pendiente_revision%')->count();
        
        return response()->json(['ganado_pendiente_revision'=>$totalGanadoPendienteRevision],200);
    }
}
