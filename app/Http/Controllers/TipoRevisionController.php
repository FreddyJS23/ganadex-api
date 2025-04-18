<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreTipoRevisionRequest;
use App\Http\Requests\UpdateTipoRevisionRequest;
use App\Http\Resources\TipoRevisionCollection;
use App\Http\Resources\TipoRevisionResource;
use App\Models\TipoRevision;

class TipoRevisionController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(TipoRevision::class, 'tipos_revision');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new TipoRevisionCollection(TipoRevision::select('id', 'tipo','codigo')->get());
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTipoRevisionRequest $request)
    {
        $tipoRevision = new TipoRevision();
        $tipoRevision->tipo=$request->tipo;
        $tipoRevision->codigo=strtoupper($request->codigo);
        $tipoRevision->save();

        return response()->json(['tipo_revision' => new TipoRevisionResource($tipoRevision)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(TipoRevision $tiposRevision)
    {
        return response()->json(['tipo_revision' => new TipoRevisionResource($tiposRevision)], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTipoRevisionRequest $request, TipoRevision $tiposRevision)
    {
        $tiposRevision->tipo=$request->tipo;
        $tiposRevision->codigo=strtoupper($request->codigo);
        $tiposRevision->save();

        return response()->json(['tipo_revision' => new TipoRevisionResource($tiposRevision)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TipoRevision $tiposRevision)
    {
        return  response()->json(['tipoRevisionID' => TipoRevision::destroy($tiposRevision->id) ?  $tiposRevision->id : ''], 200);
    }
}
