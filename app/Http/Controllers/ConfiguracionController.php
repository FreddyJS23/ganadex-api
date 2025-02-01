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
        $configuracion=Configuracion::firstWhere('user_id', Auth::id());

        return response()->json(['configuracion' => new ConfiguracionResource($configuracion)], 200);

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
        $configuracion->peso_servicio=$request->input('peso_servicio');
        $configuracion->dias_evento_notificacion=$request->input('dias_evento_notificacion');
        $configuracion->dias_diferencia_vacuna=$request->input('dias_diferencia_vacuna');
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
