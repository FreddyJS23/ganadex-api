<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGanadoRequest;
use App\Http\Requests\UpdateGanadoRequest;
use App\Http\Resources\GanadoCollection;
use App\Http\Resources\GanadoResource;
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
    
    
    public array $estado=['estado','fecha_defuncion','causa_defuncion'];
    public array $peso=['peso_nacimiento', 'peso_destete','peso_2year','peso_actual'];
    /**
     * Display a listing of the resource.
     */
    public function index() :ResourceCollection
    {
        return new GanadoCollection(Ganado::all()->where('user_id',Auth::id()));
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
      $ganado->estado()->create($request->only($this->estado));
      $ganado->evento()->create();  
     
      return response()->json(['ganado'=>new GanadoResource($ganado)],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ganado $ganado)
    {
        return response()->json(['ganado'=>new GanadoResource($ganado)],200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGanadoRequest $request, Ganado $ganado)
    {
        $ganado->fill($request->except($this->peso + $this->estado))->save();
        $ganado->peso->fill($request->only($this->peso))->save();
        $ganado->estado->fill($request->only($this->estado))->save();
       
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
