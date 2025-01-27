<?php

namespace App\Http\Controllers;

use App\Events\CrearSesionFinca;
use App\Http\Requests\StoreFincaRequest;
use App\Http\Requests\UpdateFincaRequest;
use App\Http\Resources\FincaCollection;
use App\Http\Resources\FincaResource;
use App\Models\Finca;
use Illuminate\Support\Facades\Auth;

class FincaController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Finca::class,'finca');
    }

    public function crear_sesion_finca(Finca $finca)
    {
       //para usar politicas con difierente nombre se tuvo que haber registrado previamente en el auth service provides
    $this->authorize('crear_sesion_finca',$finca);

        session()->put('finca_id', $finca->id);

        event(new CrearSesionFinca($finca));

        return response()->json(['finca'=>new FincaResource($finca)],200);
    }

    public function verificar_sesion_finca()
    {
        //para usar politicas con difierente nombre se tuvo que haber registrado previamente en el auth service provides
        $this->authorize('verificar_sesion_finca');

        $finca_id = session('finca_id');

        if($finca_id == null) return response()->json(['message'=>'no existe sesion'],401);

        $finca = Finca::find($finca_id)->first();

        return response()->json(['finca'=>new FincaResource($finca)],200);
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new FincaCollection(Finca::where('user_id',Auth::id())->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreFincaRequest $request)
    {
        $finca=new Finca;
        $finca->nombre=$request->nombre;
        $finca->user_id=$request->user()->id;
        $finca->save();

        return response()->json(['finca'=>new FincaResource($finca)],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Finca $finca)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateFincaRequest $request, Finca $finca)
    {
        $finca->nombre=$request->nombre;
        $finca->save();

        return response()->json(['finca'=>new FincaResource($finca)],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Finca $finca)
    {
        //
    }
}
