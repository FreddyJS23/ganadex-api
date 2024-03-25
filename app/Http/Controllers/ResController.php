<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResRequest;
use App\Http\Requests\UpdateResRequest;
use App\Http\Resources\ResCollection;
use App\Http\Resources\ResResource;
use App\Models\Ganado;
use App\Models\GanadoTipo;
use App\Models\Res;
use Illuminate\Support\Facades\Auth;

class ResController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(Res::class, 'res');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new ResCollection(Res::all()->where('user_id', Auth::id()));
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreResRequest $request)
    {
        $ganado = new Ganado($request->all());
        $ganado->user_id = Auth::id();
        $ganado->tipo_id = determinar_edad_res($ganado->fecha_nacimiento);
        $ganado->sexo = "M";
        $ganado->save();

        $res = new Res;
        $res->user_id = Auth::id();
        $res->ganado()->associate($ganado)->save();

        return response()->json(['res' => new ResResource($res)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Res $res)
    {
        return response()->json(['res' => new ResResource($res)]);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateResRequest $request, Res $res)
    {

        $res->ganado->fill($request->all())->save();

        return response()->json(['res' => new ResResource($res)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Res $res)
    {
        return  response()->json(['resID' => Ganado::destroy($res->ganado->id) ?  $res->id : ''], 200);
    }
}
