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
        $user->refresh();

        //crear configuracion por defecto
        $configuracion = new Configuracion();
        $configuracion->fill([
            /*Peso minimo para que este apta para un servicio  */
            'peso_servicio' => 330,
            /*Dias faltantes para crear una notificacion de evento proximo por ejemplo(evento proximo parto)*/
            'dias_evento_notificacion' => 10,
            /*Diferencia dias para que una vacuna se pueda posponer a una proxima jornada de vacunacion de la misma
            vacuna, por ejemplo, si la dosis de vacuna individual es el 10-01 y hay una jornada el 20-01 entonces la
            dosis de la vacuna individual se puede posponer hasta el 20-01 ya que la diferencia en dias es menor a 15
            esto con el fin de llevar un control de vacunas jornada vacunacion a todo el rebaño*/
            'dias_diferencia_vacuna' => 15,
        ]);
        $configuracion->user_id = $user->id;
        $configuracion->save();

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
