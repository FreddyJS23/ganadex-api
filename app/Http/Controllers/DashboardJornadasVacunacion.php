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
        $jornadasVacunacion = Jornada_vacunacion::whereBelongsTo(Auth::user())
            ->selectRaw('nombre as vacuna , MAX(prox_dosis) as prox_dosis , tipo_animal')
            ->join('vacunas', 'vacuna_id', 'vacunas.id')
            ->groupBy('vacuna', 'tipo_animal')
            ->get();

        return new ProximasJornadasVacunacionCollection($jornadasVacunacion);
    }
}
