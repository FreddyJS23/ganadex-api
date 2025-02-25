<?php

namespace App\Http\Controllers;

use App\Events\FallecimientoGanado;
use App\Http\Requests\StoreFallecimientoRequest;
use App\Http\Requests\UpdateFallecimientoRequest;
use App\Http\Resources\FallecimientoCollection;
use App\Http\Resources\FallecimientoResource;
use App\Models\CausasFallecimiento;
use App\Models\Fallecimiento;
use App\Models\Ganado;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class FallecimientoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new FallecimientoCollection(Fallecimiento::whereRelation('ganado', 'finca_id', session('finca_id'))->with('ganado:id,numero')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFallecimientoRequest $request)
    {
        $fallecimiento = new Fallecimiento();
        $ganado = Ganado::find($request->input('ganado_id'));
        $fallecimiento->fill($request->only('causas_fallecimiento_id','descripcion', 'fecha'));
        $fallecimiento->ganado()->associate($ganado);
        $fallecimiento->save();

        FallecimientoGanado::dispatch($ganado);

        return response()->json(['fallecimiento' => new FallecimientoResource($fallecimiento->load('ganado:id,numero'))], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Fallecimiento $fallecimiento)
    {
        return response()->json(['fallecimiento' => new FallecimientoResource($fallecimiento->load('ganado:id,numero'))], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFallecimientoRequest $request, Fallecimiento $fallecimiento)
    {

        $fallecimiento->fill($request->only('causas_fallecimiento_id'));
        $fallecimiento->fecha = $request->input('fecha');
        $fallecimiento->descripcion = $request->input('descripcion');
        $fallecimiento->save();

        return response()->json(['fallecimiento' => new FallecimientoResource($fallecimiento->load('ganado:id,numero'))], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Fallecimiento $fallecimiento)
    {
        return  response()->json(['fallecimientoID' => Fallecimiento::destroy($fallecimiento->id) ?  $fallecimiento->id : ''], 200);
    }
}
