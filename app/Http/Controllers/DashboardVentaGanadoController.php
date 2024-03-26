<?php

namespace App\Http\Controllers;

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
        ->firstOrFail();
        
        return response()->json(['comprador' => new CompradorResource($mejorComprador)], 200);
    }
    
    public function mejorVenta()
    {
        $mejorVenta=Venta::whereBelongsTo(Auth::user())
        ->with('ganado:id,numero')
        ->orderByDesc('precio')
        ->firstOrFail();
        return response()->json(['venta' => new VentaResource($mejorVenta)], 200);
    }
    
    public function peorVenta()
    {
    $peorVenta=Venta::whereBelongsTo(Auth::user())
        ->orderBy('precio')
        ->with('ganado:id,numero')
        ->firstOrFail();
       
        return response()->json(['venta' => new VentaResource($peorVenta)], 200);
    }
    
    public function ventasDelMes()
    {
        $ventasDelMes
        = Venta::whereBelongsTo(Auth::user())
            ->whereMonth('fecha', now()->month)
            ->whereYear('fecha', now()->year)
            ->with('ganado:id,numero')
            ->get();

        return new VentaCollection($ventasDelMes);
    }
}
