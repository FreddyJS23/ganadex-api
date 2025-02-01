<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreConfiguracionRequest;
use App\Http\Requests\UpdateConfiguracionRequest;
use App\Http\Resources\ConfiguracionResource;
use App\Models\Configuracion;
use Illuminate\Support\Facades\Auth;

class ConfiguracionController extends Controller
{

     public function __construct() {
        $this->authorizeResource(Configuracion::class,'configuracion');
    } 
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(['configuracion' => new ConfiguracionResource(Configuracion::firstWhere('user_id', Auth::id()))], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store()
    {
    }

    /**
     * Display the specified resource.
     */
    public function show(Configuracion $configuracion)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateConfiguracionRequest $request)
    {
        $configuracion=Configuracion::firstWhere('user_id', Auth::id());
        $configuracion->fill($request->all());
        $configuracion->save();

        return response()->json(['configuracion' => new ConfiguracionResource($configuracion)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Configuracion $configuracion)
    {
        //
    }
}
