<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProximosPlanesSanitarioCollection;
use App\Models\Plan_sanitario;

class DashboardPlanesSanitario extends Controller
{
    public function proximosPlanesSanitario()
    {
        $jornadasVacunacion = Plan_sanitario::query()
            ->where(
                'finca_id',
                session('finca_id')
            )
            ->where('prox_dosis', '>', now()->format('Y-m-d'))
            ->selectRaw('nombre as vacuna , MAX(prox_dosis) as prox_dosis , tipo_animal as ganado_vacunado')
            ->join('vacunas', 'vacuna_id', 'vacunas.id')
            ->orderBy('prox_dosis')
            ->groupBy('vacuna', 'tipo_animal')
            ->get();

        return new ProximosPlanesSanitarioCollection($jornadasVacunacion);
    }
}
