<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonalEnHacienda;
use App\Http\Requests\StorePersonalRequest;
use App\Http\Requests\StoreUsuarioVeterinarioRequest;
use App\Http\Requests\UpdatePersonalRequest;
use App\Http\Resources\PersonalCollection;
use App\Http\Resources\PersonalResource;
use App\Models\Personal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PersonalController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Personal::class, 'personal');
    }
    /**
     * Display a listing of the resource.
     */

     /* todo el personal */
    public function index()
    {
        return new PersonalCollection(Personal::where('user_id', Auth::id())->with('haciendas:id,nombre')->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePersonalRequest $request)
    {
        $personal = new Personal();
        $personal->fill($request->all());
        $personal->user_id = Auth::id();
        $personal->save();
        $personal->refresh();

        //guardar personal en la hacienda actual
        DB::table('hacienda_personal')->insert([
            'hacienda_id' => session('hacienda_id'),
            'personal_id' => $personal->id
        ]);

        return response()->json(['personal' => new PersonalResource($personal)], 201);
    }

 /* se registrara en la hacienda actual en sesion */
    /* se usa esa request ya que coincidi con la validacion que se necesita */
    public function registrar_personal_en_hacienda(StorePersonalEnHacienda $request)
    {
        $this->authorize('registrar_personal_hacienda');

       

        /* se hace manualmente ya que en la validacion se consulta la existencia del personal
        entonces seria reduntante consultar el personal en la validacion y luego volver a consultar en la creacion
        para insertar datos en la tabla pivote
         */
        DB::table('hacienda_personal')->insert([
            'hacienda_id' => session('hacienda_id'),
            'personal_id' => $request->input('personal_id')
        ]);


        return response()->json(['message' => 'Veterinario registrado en la hacienda actual'], 200);
    }

    public function eliminar_personal_en_hacienda(Personal $personal)
    {
        $this->authorize('eliminar_personal_hacienda', $personal);

        $personal->haciendas()->detach([session('hacienda_id')]);

        return response()->json(['message' => 'Veterinario eliminado de la hacienda']);
    }

    /**
     * Display the specified resource.
     */
    public function show(Personal $personal)
    {
        return response()->json(['personal' => new PersonalResource($personal)], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePersonalRequest $request, Personal $personal)
    {
        $personal->fill($request->all())->save();

        return response()->json(['personal' => new PersonalResource($personal)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Personal $personal)
    {
        return  response()->json(['personalID' => Personal::destroy($personal->id) ?  $personal->id : ''], 200);
    }
}
