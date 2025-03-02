<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreInsumoRequest;
use App\Http\Requests\UpdateInsumoRequest;
use App\Http\Resources\InsumoCollection;
use App\Http\Resources\InsumoResource;
use App\Models\Insumo;
use Illuminate\Support\Facades\Auth;

class InsumoController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Insumo::class, 'insumo');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new InsumoCollection(Insumo::where('hacienda_id', session('hacienda_id'))->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInsumoRequest $request)
    {
        $insumo = new Insumo();
        $insumo->fill($request->all());
        $insumo->hacienda_id = session('hacienda_id');
        $insumo->save();

        return response()->json(['insumo' => new InsumoResource($insumo)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Insumo $insumo)
    {
        return response()->json(['insumo' => new InsumoResource($insumo)], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInsumoRequest $request, Insumo $insumo)
    {
        $insumo->fill($request->all());
        $insumo->save();

        return response()->json(['insumo' => new InsumoResource($insumo)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Insumo $insumo)
    {
        return  response()->json(['insumoID' => Insumo::destroy($insumo->id) ?  $insumo->id : ''], 200);
    }
}
