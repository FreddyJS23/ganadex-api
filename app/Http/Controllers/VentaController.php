<?php

namespace App\Http\Controllers;

use App\Events\VentaGanado;
use App\Http\Requests\StoreVentaRequest;
use App\Http\Requests\UpdateVentaRequest;
use App\Http\Requests\StoreVentaLoteRequest;
use App\Http\Resources\VentaCollection;
use App\Http\Resources\VentaResource;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class VentaController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Venta::class, 'venta');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new VentaCollection(Venta::where('hacienda_id', session('hacienda_id'))->with('ganado:id,numero')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVentaRequest $request)
    {
        $venta = new Venta();
        $venta->fill($request->all());
        $venta->hacienda_id = session('hacienda_id');
        $venta->save();

        VentaGanado::dispatch($venta);

        return response()->json(['venta' => new VentaResource($venta->load('ganado:id,numero'))], 201);
    }

    /**
     * Store a newly created resource in storage for batch sales.
     */
    public function storeBatch(StoreVentaLoteRequest $request)
    {
        $ventas = collect($request->ganado_ids)->map(function ($ganadoId) use ($request) {
            $venta = new Venta();
            $venta->fill([
                'fecha' => $request->fecha,
                'ganado_id' => $ganadoId,
                'comprador_id' => $request->comprador_id,
                'hacienda_id' => session('hacienda_id'),
            ]);
            $venta->save();

            VentaGanado::dispatch($venta);

            return $venta;
        });

        return response()->json(['ventas' => VentaResource::collection($ventas)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Venta $venta)
    {
        return response()->json(['venta' => new VentaResource($venta->load('ganado:id,numero'))], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVentaRequest $request, Venta $venta)
    {
        $venta->fill($request->all());
        $venta->save();

        return response()->json(['venta' => new VentaResource($venta->load('ganado:id,numero'))], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Venta $venta)
    {
        return  response()->json(['ventaID' => Venta::destroy($venta->id) ?  $venta->id : ''], 200);
    }
}
