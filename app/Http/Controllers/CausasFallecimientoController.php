<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCausasFallecimientosRequest;
use App\Http\Requests\UpdateCausasFallecimientosRequest;
use App\Http\Resources\CausasFallecimientosCollection;
use App\Http\Resources\CausasFallecimientosResource;
use App\Models\CausasFallecimiento;

class CausasFallecimientoController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(CausasFallecimiento::class, 'causas_fallecimiento');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new CausasFallecimientosCollection(CausasFallecimiento::select('id', 'causa')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCausasFallecimientosRequest $request)
    {
        $causaFallecimiento = new CausasFallecimiento($request->all());
        $causaFallecimiento->save();
        return response()->json(['causa_fallecimiento' => new CausasFallecimientosResource($causaFallecimiento)],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(CausasFallecimiento $causasFallecimiento)
    {
        return response()->json(['causa_fallecimiento' => new CausasFallecimientosResource($causasFallecimiento)], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCausasFallecimientosRequest $request, CausasFallecimiento $causaFallecimiento)
    {
        $causaFallecimiento->fill($request->all());
        $causaFallecimiento->save();
        return response()->json(['causa_fallecimiento' => new CausasFallecimientosResource($causaFallecimiento, 200)]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CausasFallecimiento $causasFallecimiento)
    {
        return  response()->json(['causaFallecimientoID' => CausasFallecimiento::destroy($causasFallecimiento->id)
            ?  $causasFallecimiento->id
            : ''], 200);
    }
}
