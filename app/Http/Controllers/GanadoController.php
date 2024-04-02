<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGanadoRequest;
use App\Http\Requests\UpdateGanadoRequest;
use App\Http\Resources\GanadoCollection;
use App\Http\Resources\GanadoResource;
use App\Http\Resources\PartoResource;
use App\Http\Resources\RevisionResource;
use App\Http\Resources\ServicioResource;
use App\Models\Ganado;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\JsonResponse;

class GanadoController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Ganado::class,'ganado');
    }   
    
    
    public array $estado=['estado_id'];
    public array $peso=['peso_nacimiento', 'peso_destete','peso_2year','peso_actual'];
    /**
     * Display a listing of the resource.
     */
    public function index() :ResourceCollection
    {
        return new GanadoCollection(Ganado::doesntHave('toro')->where('user_id',Auth::id())->with(['peso','evento','estados'])->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGanadoRequest $request) :JsonResponse
    {
      $ganado=new Ganado;
      $ganado->fill($request->except($this->estado + $this->peso));
      $ganado->user_id=Auth::id();
      $ganado->save();
      
      $ganado->peso()->create($request->only($this->peso));
      $ganado->estados()->sync($request->only($this->estado));
      $ganado->evento()->create();  
     
      return response()->json(['ganado'=>new GanadoResource($ganado)],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ganado $ganado)
    {
        $ultimaRevision = $ganado->revisionReciente;
        $ultimoServicio = $ganado->servicioReciente;
        $ultimoParto = $ganado->partoReciente;
        $ganado->loadCount('servicios')->loadCount('revision')->loadCount('parto');
        $efectividad=fn(int $resultadoAlcanzado,int $resultadoPrevisto)=>$resultadoAlcanzado * 100 / $resultadoPrevisto;  

        return response()->json([
            'ganado'=>new GanadoResource($ganado),
            'servicio_reciente'=>$ultimoServicio ? new ServicioResource($ultimoServicio) : null,
            'total_servicios'=>$ganado->servicios_count,
            'revision_reciente'=> $ultimaRevision ? new RevisionResource($ultimaRevision) : null,
            'total_revisiones'=>$ganado->revision_count,
            'parto_reciente'=> $ultimoParto ? new PartoResource($ultimoParto) : null,
            'total_partos'=>$ganado->parto_count,
            'efectividad'=>$ganado->parto_count ? round($efectividad($ganado->parto_count, $ganado->servicios_count),2) : null
        ],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGanadoRequest $request, Ganado $ganado)
    {
        $ganado->fill($request->except($this->peso + $this->estado))->save();
        $ganado->peso->fill($request->only($this->peso))->save();
        $ganado->estados()->sync($request->only($this->estado));
       
        return response()->json(['ganado'=>new GanadoResource($ganado)],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ganado $ganado)
    {
        return  response()->json(['ganadoID' => Ganado::destroy($ganado->id) ?  $ganado->id : ''], 200);
    }
}
