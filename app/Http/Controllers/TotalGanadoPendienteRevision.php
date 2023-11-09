<?php

namespace App\Http\Controllers;


use App\Models\Ganado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TotalGanadoPendienteRevision extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request)
    {
     
         $totalGanadoPendienteRevision=Ganado::whereBelongsTo(Auth::user())
         ->whereRelation('estados','estado','pendiente_revision')
         ->count();
        
        return response()->json(['ganado_pendiente_revision'=>$totalGanadoPendienteRevision],200);
    }
}
