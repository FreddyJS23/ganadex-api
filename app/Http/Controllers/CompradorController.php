<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCompradorRequest;
use App\Http\Requests\UpdateCompradorRequest;
use App\Http\Resources\CompradorCollection;
use App\Http\Resources\CompradorResource;
use App\Models\Comprador;
use Illuminate\Support\Facades\Auth;

class CompradorController extends Controller
{
     public function __construct()
    {
        $this->authorizeResource(Comprador::class, 'comprador');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new CompradorCollection(Comprador::where('finca_id',session('finca_id'))->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCompradorRequest $request)
    {
        $comprador = new Comprador;
        $comprador->fill($request->all());
        $comprador->finca_id = session('finca_id');
        $comprador->save();

        return response()->json(['comprador' => new CompradorResource($comprador)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Comprador $comprador)
    {
        return response()->json(['comprador' => new CompradorResource($comprador)], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCompradorRequest $request, Comprador $comprador)
    {
        $comprador->fill($request->all());
        $comprador->save();

        return response()->json(['comprador' => new CompradorResource($comprador)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Comprador $comprador)
    {
        return  response()->json(['compradorID' => Comprador::destroy($comprador->id) ?  $comprador->id : ''], 200);
    }
}
