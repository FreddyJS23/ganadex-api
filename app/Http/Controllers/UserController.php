<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\Configuracion;
use App\Models\Hacienda;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(User::class, 'user');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        $user = new User();
        $user->password = hash::make($request->password);
        $user->fill($request->except('password'));
        $user->assignRole('admin');
        $user->save();
        Configuracion::factory()->for($user)->create();

        return response()->json(['message' => 'usuario creado'], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        $user->load('haciendas');
        $user->haciendas = $user->haciendas->map(
            function (Hacienda $hacienda) {
                $hacienda->fecha_creacion = $hacienda->created_at->format('d-m-Y');
                return $hacienda;
            }
        );

        return response()->json(['user' => new UserResource($user)], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUserRequest $request, User $user)
    {
        $user->password = Hash::make($request->password);
        $user->fill($request->all())->save();

        return  response()->json(['user' => new UserResource($user)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        return  response()->json(['userID' => User::destroy($user->id) ?  $user->id : ''], 200);
    }
}
