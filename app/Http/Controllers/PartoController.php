<?php

namespace App\Http\Controllers;

use App\Events\NaceMacho;
use App\Events\PartoHecho;
use App\Http\Requests\StorePartoRequest;
use App\Http\Requests\UpdatePartoRequest;
use App\Http\Resources\PartoCollection;
use App\Http\Resources\PartoResource;
use App\Models\Estado;
use App\Models\Ganado;
use App\Models\GanadoTipo;
use App\Models\Parto;
use App\Models\Peso;
use DateTime;
use Illuminate\Support\Facades\Auth;

class PartoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Ganado $ganado)
    {
        return new PartoCollection(Parto::whereBelongsTo($ganado)->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePartoRequest $request, Ganado $ganado)
    {
        $fecha=new DateTime();
        $parto=new Parto;
        $parto->fill($request->only(['observacion']));
        $parto->fecha=$fecha->format('Y-m-d');
        $toro=$ganado->servicioReciente->toro;
        
        $parto->ganado()->associate($ganado);
        $parto->toro()->associate($toro);
        
        $cria=new Ganado;

        $cria->fill($request->except(['observacion','peso_nacimiento']));
        $cria->fecha_nacimiento=$fecha->format('Y-m-d');
        $cria->tipo_id=GanadoTipo::where('tipo','becerro')->first()->id;
        $cria->origen='local';
        $cria->user_id=Auth::id();
        $cria->save();
        $cria->evento()->create(); 
        $estado=new Estado(['estado'=>'sano']);
        $estado->ganado()->associate($cria)->save();
        $peso_nacimiento=new Peso($request->only(['peso_nacimiento']));
        $peso_nacimiento->ganado()->associate($cria)->save();
        
        $parto->ganado_cria()->associate($cria)->save();

        PartoHecho::dispatch($parto);
        NaceMacho::dispatchIf($cria->sexo == "M",$cria);

        return response()->json(['parto'=>new PartoResource($parto)],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ganado $ganado, Parto $parto)
    {
        return response()->json(['parto'=>new PartoResource($parto)]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePartoRequest $request,Ganado $ganado, Parto $parto)
    {
        $parto->fill($request->only(['observacion']))->save();
        
        return response()->json(['parto'=>new PartoResource($parto)],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ganado $ganado,Parto $parto)
    {
        return  response()->json(['partoID' => Parto::destroy($parto->id) ?  $parto->id : ''], 200);
    }
}
