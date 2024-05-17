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
        $mejorComprador= Comprador::whereBelongsTo(Auth::user())
        ->withCount('ventas')
        ->orderByDesc('ventas_count')
        ->first();
        
        return response()->json(['comprador' =>$mejorComprador ? new CompradorResource($mejorComprador) : ''], 200);
    }
    
    public function mejorVenta()
    {
        $mejorVenta=Venta::whereBelongsTo(Auth::user())
        ->with('ganado:id,numero')
        ->orderByDesc('precio')
        ->first();
        return response()->json(['venta' =>$mejorVenta ? new VentaResource($mejorVenta) : ''], 200);
    }
    
    public function peorVenta()
    {
    $peorVenta=Venta::whereBelongsTo(Auth::user())
        ->orderBy('precio')
        ->with('ganado:id,numero')
        ->first();
       
        return response()->json(['venta' =>$peorVenta ? new VentaResource($peorVenta) : ''], 200);
    }
    
    public function ventasDelMes()
    {
        $ventasDelMes
        = Venta::whereBelongsTo(Auth::user())
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->with('ganado:id,numero')
            ->get();

         return new VentaCollection($ventasDelMes) ;
    }
   
    public function balanceAnualVentas()
    {
        $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
      
    $balanceMesesVentas = Venta::whereBelongsTo(Auth::user())
    ->selectRaw("DATE_FORMAT(fecha,'%m') as mes, COUNT(id) as ventas")
        ->whereYear('fecha', now()->format('Y'))
            ->groupBy('mes')
            ->get()
            ->toArray();
            
$balanceAnual=[];

foreach ($meses as $keyMes => $mes) {

$cantidadVentasMes=0;

/* Iterar resultado sql para sincronizar numero mes y ventas del mes, 
al crear array con el nombre del mes y las ventas del mes */
foreach ($balanceMesesVentas as $mesBalance)  if(intval($mesBalance['mes']) == $keyMes +  1)  $cantidadVentasMes= $mesBalance['ventas'];

array_push($balanceAnual,['mes'=>$mes,'ventas'=>$cantidadVentasMes ]);
}
        return new BalanceAnualVentasGanadoCollection($balanceAnual);
    }
}
