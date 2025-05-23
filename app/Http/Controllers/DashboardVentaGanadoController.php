<?php

namespace App\Http\Controllers;

use App\Http\Resources\BalanceAnualVentasGanadoCollection;
use App\Http\Resources\CompradorCollection;
use App\Http\Resources\CompradorResource;
use App\Http\Resources\VentaCollection;
use App\Http\Resources\VentaResource;
use App\Models\Comprador;
use App\Models\Venta;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardVentaGanadoController extends Controller
{
    public function mejorComprador()
    {
        $mejorComprador = Comprador::where('hacienda_id', session('hacienda_id'))
            ->withCount('ventas')
            ->orderByDesc('ventas_count')
            ->first();

        return response()->json(['comprador' => $mejorComprador ? new CompradorResource($mejorComprador) : ''], 200);
    }

    public function mejorVenta()
    {
        $mejorVenta = Venta::where('hacienda_id', session('hacienda_id'))
            ->with('ganado:id,numero')
            ->orderByDesc('precio')
            ->first();
        return response()->json(['venta' => $mejorVenta ? new VentaResource($mejorVenta) : ''], 200);
    }

    public function peorVenta()
    {
        $peorVenta = Venta::where('hacienda_id', session('hacienda_id'))
            ->orderBy('precio')
            ->with('ganado:id,numero')
            ->first();

        return response()->json(['venta' => $peorVenta ? new VentaResource($peorVenta) : ''], 200);
    }

    public function ventasDelMes()
    {
        $ventasDelMes
        = Venta::where('hacienda_id', session('hacienda_id'))
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->with('ganado:id,numero')
            ->get();

         return new VentaCollection($ventasDelMes);
    }

    public function balanceAnualVentas(Request $request)
    {
        $regexYear = "/^[2][0-9][0-9][0-9]$/";

        $year = preg_match($regexYear, $request->query('year')) ? $request->query('year') : now()->format('Y');

        $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

        $balanceMesesVentas = Venta::where('hacienda_id', session('hacienda_id'))
            ->selectRaw("DATE_FORMAT(fecha,'%m') as mes, COUNT(id) as ventas")
            ->whereYear('fecha', $year)
            ->groupBy('mes')
            ->get()
            ->toArray();

        $balanceAnual = [];

        foreach ($meses as $keyMes => $mes) {
            /* inicializar cantidad de ventas del mes, para asi discriminar si existe o no ventas del mes */
            $cantidadVentasMes = 0;

            /* Iterar resultado sql para sincronizar numero mes y ventas del mes,
            al crear array con el nombre del mes y las ventas del mes */

            /* Esto se hace con el fin de llenar la grafica con todos los meses,
            pudiendo haber meses que no hayan tenido ventas, entonces se lo que se hace es filtrar que meses tuvieron ventas */
            foreach ($balanceMesesVentas as $mesBalance) {

                if (intval($mesBalance['mes']) == $keyMes +  1) {
                    $cantidadVentasMes = $mesBalance['ventas'];
                }
            }

            array_push($balanceAnual, ['mes' => $mes,'ventas' => $cantidadVentasMes ]);
        }
        return new BalanceAnualVentasGanadoCollection($balanceAnual);
    }
}
