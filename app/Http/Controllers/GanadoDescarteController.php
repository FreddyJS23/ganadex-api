<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDescartarGanado;
use App\Http\Requests\StoreGanadoDescarteRequest;
use App\Http\Requests\StoreResRequest;
use App\Http\Requests\UpdateGanadoDescarteRequest;
use App\Http\Requests\UpdateResRequest;
use App\Http\Resources\GanadoDescarteCollection;
use App\Http\Resources\ResCollection;
use App\Http\Resources\GanadoDescarteResource;
use App\Models\Ganado;
use App\Models\GanadoTipo;
use App\Models\GanadoDescarte;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Auth;

class GanadoDescarteController extends Controller
{
    public array $peso = ['peso_nacimiento', 'peso_destete', 'peso_2year', 'peso_actual'];

     public function __construct()
    {
        $this->authorizeResource(GanadoDescarte::class, 'ganado_descarte');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new GanadoDescarteCollection(GanadoDescarte::whereIn('finca_id', session('finca_id'))->get());
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGanadoDescarteRequest $request)
    {
        $ganado = new Ganado($request->all());
        $ganado->finca_id = session('finca_id')[0];
        $ganado->tipo_id = determinar_edad_res($ganado->fecha_nacimiento);
        $ganado->sexo = "M";
        $ganado->save();
        $ganado->peso()->create($request->only($this->peso));

        $ganadoDescarte = new GanadoDescarte;
        $ganadoDescarte->finca_id = session('finca_id')[0];
        $ganadoDescarte->ganado()->associate($ganado)->save();

        return response()->json(['ganado_descarte' => new GanadoDescarteResource($ganadoDescarte)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(GanadoDescarte $ganadoDescarte)
    {
        return response()->json(['ganado_descarte' => new GanadoDescarteResource($ganadoDescarte)], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGanadoDescarteRequest $request, GanadoDescarte $ganadoDescarte)
    {

        $ganadoDescarte->ganado->fill($request->all())->save();

        return response()->json(['ganado_descarte' => new GanadoDescarteResource($ganadoDescarte)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(GanadoDescarte $ganadoDescarte)
    {
        return  response()->json(['ganado_descarteID' => Ganado::destroy($ganadoDescarte->ganado->id) ?  $ganadoDescarte->id : ''], 200);
    }

    public function descartar(StoreDescartarGanado $request)
    {
        $ganadoDescarte=new GanadoDescarte;
        $ganadoDescarte->ganado_id=$request->ganado_id;
        $ganadoDescarte->finca_id=session('finca_id')[0];
        $ganadoDescarte->save();
        return response()->json(['ganado_descarte' => new GanadoDescarteResource($ganadoDescarte)], 201);
    }
}
