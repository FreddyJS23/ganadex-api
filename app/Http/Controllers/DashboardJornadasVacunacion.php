<?php

namespace App\Http\Controllers;

use App\Http\Resources\ProximasJornadasVacunacionCollection;
use App\Models\Jornada_vacunacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardJornadasVacunacion extends Controller
{
    public function proximasJornadasVacunacion()
    {
        $jornadasVacunacion = Jornada_vacunacion::where('finca_id',session('finca_id'))
            ->selectRaw('nombre as vacuna , MAX(prox_dosis) as prox_dosis , tipo_animal as ganado_vacunado')
            ->join('vacunas', 'vacuna_id', 'vacunas.id')
            ->orderBy('prox_dosis')
            ->groupBy('vacuna', 'tipo_animal')
            ->get();

        return new ProximasJornadasVacunacionCollection($jornadasVacunacion);
    }
}
