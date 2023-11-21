<?php

namespace App\Http\Controllers;

use App\Http\Resources\VentaLecheCollection;
use App\Models\Precio;
use App\Models\VentaLeche;
use Illuminate\Support\Facades\Auth;

class DashboardVentaLecheController extends Controller
{
    public function precioActual()
    {
        $precioActual = Precio::whereBelongsTo(Auth::user())->latest('fecha')->first();

        return response()->json(['precio_actual' => $precioActual->precio]);
    }

    public function variacionPrecio()
    {
        $precioActual = Precio::whereBelongsTo(Auth::user())->latest('fecha')->first();
        $precioAnterior = Precio::whereBelongsTo(Auth::user())->latest('fecha')->where('fecha', '<', $precioActual->fecha)->first();

        $variacion = fn (float $precioAnterior, float $precioActual) =>
        $precioAnterior - $precioActual * 100 / $precioAnterior;

        return response()->json(['variacion' => $precioAnterior ? $variacion($precioAnterior->precio, $precioActual->precio) : null]);
    }

    public function gananciasDelMes()
    {
        $sumaGanaciaDelMes
            = VentaLeche::whereBelongsTo(Auth::user())
            ->whereMonth('venta_leches.fecha', now()->month)
            ->whereYear('venta_leches.fecha', now()->year)
            ->join('precios', 'precio_id', '=', 'precios.id')
            ->sum('precio');

        return response()->json(['ganancias' => $sumaGanaciaDelMes]);
    }

    public function ventasDelMes()
    {
        $ventasDelMes
            = VentaLeche::whereBelongsTo(Auth::user())
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->get();

        return new VentaLecheCollection($ventasDelMes);
    }
}
