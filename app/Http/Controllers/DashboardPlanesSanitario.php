<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProximosPlanesSanitarioCollection;
use App\Models\Plan_sanitario;

class DashboardPlanesSanitario extends Controller
{
    public function proximosPlanesSanitario()
    {
        $jornadasVacunacion = Plan_sanitario::query()
        ->select('nombre as vacuna','prox_dosis','ganado_vacunado')
        ->join('vacunas', 'plan_sanitarios.vacuna_id', '=', 'vacunas.id')
        ->where('plan_sanitarios.hacienda_id', session('hacienda_id'))
        ->where('plan_sanitarios.prox_dosis', '>', now()->format('Y-m-d'))
        ->whereIn('plan_sanitarios.id', function ($query) {
            $query->selectRaw('MAX(id)')
                ->from('plan_sanitarios')
                ->where('hacienda_id', session('hacienda_id'))
                ->groupBy('vacuna_id');
        })
        ->orderBy('plan_sanitarios.prox_dosis')
        ->get();

    return new ProximosPlanesSanitarioCollection($jornadasVacunacion);

    }
}
