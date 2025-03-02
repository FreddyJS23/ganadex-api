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
        $causasFrecuentes = Fallecimiento::whereRelation('ganado', 'hacienda_id', session('hacienda_id'))
            ->select(DB::raw('count(*) as fallecimientos'), 'causa')
            ->join('causas_fallecimientos','causas_fallecimiento_id','=','causas_fallecimientos.id')
            ->groupBy('causa')
            ->limit(5)
            ->orderBy('fallecimientos', 'desc')
            ->get();

        $totalFallecidos
            = Fallecimiento::whereRelation('ganado', 'hacienda_id', session('hacienda_id'))
            ->count();

        return response()->json(
            [
            'total_fallecidos' => $totalFallecidos,
            'causas_frecuentes' => CausasFallecimientosDashboardResource::collection($causasFrecuentes),
            ]
        );
    }
}
