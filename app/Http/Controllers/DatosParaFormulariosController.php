<?php

namespace App\Http\Controllers;

use App\Http\Resources\CargosPersonalCollection;
use App\Http\Resources\NovillaAMontarCollection;
use App\Http\Resources\VeterinariosDisponiblesCollection;
use App\Models\Cargo;
use App\Models\Leche;
use App\Models\Personal;
use App\Models\Peso;
use App\Models\Vacuna;
use App\Models\Venta;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DatosParaFormulariosController extends Controller
{
    public function novillasParaMontar()
    {
        $novillasAmontar = Peso::whereHas('ganado', function (Builder $query) {
            $query->where('finca_id', session('finca_id'));
        })->where('peso_actual', '>=', 330)->get();

        return new NovillaAMontarCollection($novillasAmontar);
    }
    public function cargosPersonalDisponible()
    {
        $cargos = Cargo::get();

        return new CargosPersonalCollection($cargos);
    }
    public function veterinariosDisponibles()
    {
        return new VeterinariosDisponiblesCollection(Personal::select('id','nombre')
        ->where('cargo_id',2)
        ->whereIn('finca_id',session('finca_id'))
        ->get());
    }

    public function añosVentasGanado()
    {
        $añosVentasGanado = Venta::whereIn('finca_id',session('finca_id'))
            ->selectRaw('DATE_FORMAT(fecha,"%Y") as año')
            ->groupBy('año')
            ->orderBy('año', 'desc')
            ->get();

        $añosVentasGanado->transform(function ($item, $key) {
            $item->año=intval($item->año);
            return $item;
        });

        return response()->json(['años_ventas_ganado' => $añosVentasGanado]);
    }
    public function añosProduccionLeche()
    {
        $añosVentaProduccionLeche = Leche::whereIn('finca_id',session('finca_id'))
            ->selectRaw('DATE_FORMAT(fecha,"%Y") as año')
            ->groupBy('año')
            ->orderBy('año', 'desc')
            ->get();

        $añosVentaProduccionLeche->transform(function ($item, $key) {
            $item->año=intval($item->año);
            return $item;
        });

        return response()->json(['años_produccion_leche' => $añosVentaProduccionLeche]);
    }

    public function vacunasDisponibles()
    {
        $vacunasDisponibles = Vacuna::select('id','nombre')
            ->get();

        return response()->json(['vacunas_disponibles' => $vacunasDisponibles]);
    }
}
