<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreVacunaRequest;
use App\Http\Requests\UpdateVacunaRequest;
use App\Http\Resources\VacunasCollection;
use App\Http\Resources\VacunasResource;
use App\Models\Vacuna;

class VacunaController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new VacunasCollection(Vacuna::all());
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVacunaRequest $request)
    {
        $vacuna = new Vacuna();
        $vacuna->fill($request->all());
        $vacuna->save();

        return response()->json(['vacuna' => new VacunasResource($vacuna)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Vacuna $vacuna)
    {
        return response()->json(['vacuna' => new VacunasResource($vacuna)], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateVacunaRequest $request, Vacuna $vacuna)
    {
        $vacuna->fill($request->all());
        $vacuna->save();

        return response()->json(['vacuna' => new VacunasResource($vacuna)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Vacuna $vacuna)
    {
    return  response()->json(['vacunaID' => Vacuna::destroy($vacuna->id) ?  $vacuna->id : ''], 200);
    }
}
