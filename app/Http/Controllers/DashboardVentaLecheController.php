<?php

namespace App\Http\Controllers;

use App\Http\Resources\BalanceMensualVentaLecheCollection;
use App\Http\Resources\VentaLecheCollection;
use App\Models\Precio;
use App\Models\VentaLeche;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardVentaLecheController extends Controller
{
    public function precioActual()
    {
        $precioActual = Precio::whereIn('finca_id',session('finca_id'))->latest('fecha')->first();

        return response()->json(['precio_actual' => $precioActual->precio ?? 0]);
    }

    public function variacionPrecio()
    {
        $precioActual = Precio::whereIn('finca_id',session('finca_id'))->latest('fecha')->first() ?? 0;
        $precioAnterior = !$precioActual == 0  ? Precio::whereIn('finca_id',session('finca_id'))->latest('fecha')->where('fecha', '<', $precioActual->fecha)->first() : 0;

        $variacion = fn (float $precioAnterior, float $precioActual) =>
        $precioAnterior - $precioActual * 100 / $precioAnterior;

        return response()->json(['variacion' => $precioAnterior ? $variacion($precioAnterior->precio, $precioActual->precio) : 0]);
    }

    public function gananciasDelMes()
    {
        $sumaGanaciaDelMes
            = VentaLeche::whereIn('finca_id',session('finca_id'))
            ->whereMonth('venta_leches.fecha', now()->month)
            ->whereYear('venta_leches.fecha', now()->year)
            ->join('precios', 'precio_id', '=', 'precios.id')
            ->sum('precio');

        return response()->json(['ganancias' => $sumaGanaciaDelMes]);
    }

    public function ventasDelMes()
    {
        $ventasDelMes
            = VentaLeche::whereIn('finca_id',session('finca_id'))
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->get();

        return new VentaLecheCollection($ventasDelMes);
    }
    public function balanceMensual(Request $request)
    {
        $regexMonthOneDigit = "/^[1-9]$/";
        $regexMonthTwoDigit = "/^[1][0-2]$/";
        $month = 0;
        $monthActual = intval(now()->format('m'));
        $monthQueryParam = intval($request->query('month'));

        if (preg_match($regexMonthOneDigit, $monthQueryParam)) $month =$monthQueryParam;
        else if (preg_match($regexMonthTwoDigit, $monthQueryParam)) $month = $monthQueryParam;
        else $month = $monthActual;


        $ventasDelMes
            = VentaLeche::whereIn('finca_id',session('finca_id'))
            ->select('fecha','cantidad')
            ->whereMonth('fecha', $month)
            ->orderBy('fecha')
            ->whereYear('fecha', now()->year)
            ->get();

        return new BalanceMensualVentaLecheCollection($ventasDelMes);
    }
}
