<?php

namespace App\Http\Controllers;


use App\Http\Resources\CausasFallecimientosDashboardResource;
use App\Models\Fallecimiento;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardFallecimientosController extends Controller
{
    public function causasMuertesFrecuentes()
    {
        $causasFrecuentes = Fallecimiento::whereRelation('ganado', 'user_id', Auth::id())
            ->select(DB::raw('count(*) as fallecimientos'), 'causa')
            ->groupBy('causa')
            ->get();

        $totalFallecidos
            = Fallecimiento::whereRelation('ganado', 'user_id', Auth::id())
            ->count();

        return response()->json([
            'total_fallecidos' => $totalFallecidos,
            'causas_frecuentes' => CausasFallecimientosDashboardResource::collection($causasFrecuentes),
        ]);
    }
}
