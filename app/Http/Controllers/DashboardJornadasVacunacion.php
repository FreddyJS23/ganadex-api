<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProximasJornadasVacunacionCollection;
use App\Models\Jornada_vacunacion;

class DashboardJornadasVacunacion extends Controller
{
    public function proximasJornadasVacunacion()
    {
        $jornadasVacunacion = Jornada_vacunacion::query()
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

        return new ProximasJornadasVacunacionCollection($jornadasVacunacion);
    }
}
