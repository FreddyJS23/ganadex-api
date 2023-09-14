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
      $ganado->fill($request->all());
      $ganado->user_id=Auth::id();
      $ganado->save();
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
        $ganado->fill($request->all())->save();
        
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
