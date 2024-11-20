<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVentaLecheRequest;
use App\Http\Requests\UpdateVentaLecheRequest;
use App\Http\Resources\VentaLecheCollection;
use App\Http\Resources\VentaLecheResource;
use App\Models\VentaLeche;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class VentaLecheController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new VentaLecheCollection(VentaLeche::whereIn('finca_id',session('finca_id'))->latest('fecha')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVentaLecheRequest $request)
    {
        dd(4);
        $ventaLeche=new VentaLeche;
        $ventaLeche->fill($request->only('cantidad','precio_id'));
        $ventaLeche->finca_id=session('finca_id')[0];
        $ventaLeche->fecha=Carbon::now()->format('Y-m-d');
        $ventaLeche->save();

        return response()->json(['venta_leche'=> new VentaLecheResource($ventaLeche)],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(VentaLeche $ventaLeche)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVentaLecheRequest $request, VentaLeche $ventaLeche)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(VentaLeche $ventaLeche)
    {
        //
    }
}
