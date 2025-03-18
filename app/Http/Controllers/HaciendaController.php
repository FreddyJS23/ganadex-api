<?php

namespace App\Http\Controllers;

use App\Events\CrearSesionHacienda;
use App\Http\Requests\StoreHaciendaRequest;
use App\Http\Requests\StoreUsuarioVeterinarioRequest;
use App\Http\Requests\UpdateHaciendaRequest;
use App\Http\Resources\HaciendaCollection;
use App\Http\Resources\HaciendaResource;
use App\Models\Hacienda;
use App\Models\Personal;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HaciendaController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Hacienda::class, 'hacienda');
    }

    public function crear_sesion_hacienda(Hacienda $hacienda)
    {
        //dd($hacienda);
        //para usar politicas con difierente nombre se tuvo que haber registrado previamente en el auth service provides
        $this->authorize('crear_sesion_hacienda', $hacienda);

        session()->put('hacienda_id', $hacienda->id);

        event(new CrearSesionHacienda($hacienda));

        return response()->json(['hacienda' => new HaciendaResource($hacienda)], 200);
    }

    public function verificar_sesion_hacienda()
    {
        //para usar politicas con difierente nombre se tuvo que haber registrado previamente en el auth service provides
        $this->authorize('verificar_sesion_hacienda');

        $hacienda_id = session('hacienda_id');

        if ($hacienda_id == null) {
            return response()->json(['message' => 'no existe sesion'], 401);
        }

        $hacienda = Hacienda::find($hacienda_id);

        return response()->json(['hacienda' => new HaciendaResource($hacienda)], 200);
    }

    public function cambiar_hacienda_sesion(Request $request,Hacienda $hacienda)
    {
        $this->authorize('cambiar_hacienda_sesion');

        if($hacienda == null){
            return response()->json(['message' => 'no existe esa hacienda'], 404);
        }

        session()->put('hacienda_id', $hacienda->id);

        return response()->json(['hacienda' => new HaciendaResource($hacienda)], 200);
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new HaciendaCollection(Hacienda::where('user_id', Auth::id())->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreHaciendaRequest $request)
    {
        $user=User::find($request->user()->id);
        $hacienda = new Hacienda();
        $hacienda->nombre = $request->nombre;
        $hacienda->user_id = $user->id;
        $hacienda->save();


        /* en caso que sea la primera hacienda del usuario se debe crear la sesion de la hacienda */
        if($user->haciendas->count() == 1)
          {
            session()->put('hacienda_id', $hacienda->id);
        }


        return response()->json(['hacienda' => new HaciendaResource($hacienda)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Hacienda $hacienda)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateHaciendaRequest $request, Hacienda $hacienda)
    {
        $hacienda->nombre = $request->nombre;
        $hacienda->save();

        return response()->json(['hacienda' => new HaciendaResource($hacienda)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Hacienda $hacienda)
    {
        //
    }
}
