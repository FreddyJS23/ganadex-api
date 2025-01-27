<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioVeterinarioRequest;
use App\Http\Requests\UpdateUsuarioVeterinarioRequest;
use App\Http\Resources\UsuarioVeterinarioCollection;
use App\Http\Resources\UsuarioVeterinarioResource;
use App\Models\Personal;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UsuarioVeterinarioController extends Controller
{

    public function __construct()
    {
        $this->authorizeResource(UsuarioVeterinario::class,'usuarios_veterinario');
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return new UsuarioVeterinarioCollection(UsuarioVeterinario::where('admin_id',Auth::id())->get());
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUsuarioVeterinarioRequest $request)
    {
        $nameUsuarioAleatorio=fn()=> 'usuario'.rand(1,100) . rand(1,100) ;

        $veterinario=Personal::where('id',$request->input('personal_id'))->first();
        $usuarioVeterinario = new UsuarioVeterinario;
        $usuario=new User();
        $usuario->usuario=explode(' ',$veterinario->nombre)[0] . rand(1,100) ;

        if(strlen($usuario->usuario) < 5 || strlen($usuario->usuario) > 20){
            $usuario->usuario=$nameUsuarioAleatorio();
        }

        $usuario->password=Hash::make('123456');
        $usuario->assignRole('veterinario');
        $usuario->save();

        $usuarioVeterinario->admin_id=Auth::id();
        $usuarioVeterinario->user_id=$usuario->id;
        $usuarioVeterinario->personal_id=$request->input('personal_id');
        $usuarioVeterinario->save();
        return response()->json(['usuario_veterinario' => new UsuarioVeterinarioResource($usuarioVeterinario)], 201);

    }

    /**
     * Display the specified resource.
     */
    public function show(UsuarioVeterinario $usuarioVeterinario)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(UsuarioVeterinario $usuariosVeterinario)
    {
        return  response()->json(['usuarioVeterinarioID' => UsuarioVeterinario::destroy($usuariosVeterinario->id) ?  $usuariosVeterinario->id : ''], 200);
    }
}
