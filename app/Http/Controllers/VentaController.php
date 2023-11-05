<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVentaRequest;
use App\Http\Requests\UpdateVentaRequest;
use App\Http\Resources\VentaCollection;
use App\Http\Resources\VentaResource;
use App\Models\Venta;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class VentaController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Venta::class,'venta');
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new VentaCollection(Venta::whereBelongsTo(Auth::user())->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVentaRequest $request)
    {
        $venta = new Venta;
        $venta->fill($request->all());
        $venta->user_id = Auth::id();
        $venta->fecha = Carbon::now()->format('Y-m-d');
        $venta->save();

        return response()->json(['venta' => new VentaResource($venta)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Venta $venta)
    {
        return response()->json(['venta' => new VentaResource($venta)], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVentaRequest $request, Venta $venta)
    {
        $venta->fill($request->all());
        $venta->save();

        return response()->json(['venta' => new VentaResource($venta)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Venta $venta)
    {
        return  response()->json(['ventaID' => Venta::destroy($venta->id) ?  $venta->id : ''], 200);
    }
}
