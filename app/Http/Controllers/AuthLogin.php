<?php

namespace App\Http\Controllers;

use App\Events\CrearSesionHacienda;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\ConfiguracionResource;
use App\Models\Configuracion;
use App\Models\Hacienda;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use Illuminate\Support\Facades\Auth;

class AuthLogin extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(LoginRequest $request)
    {
        //buscar usuario
        $user = User::firstWhere('usuario', $request->usuario);

        //usuario no encontrado
        if (!$user) {
            return response()->json(['message' => 'invalid user'], 401);
        }

        //intentar autenticar
        if (!Auth::attempt($request->only(['usuario', 'password']))) {
            return response()->json(['message' => 'invalid password'], 401);
        }

        $request->session()->regenerate();
        activity('Login')->log('Login');
        $rol = $user->hasRole('admin') ? 'admin' : 'veterinario';
        //notificar en el client si el login incluye inicio de sesion hacienda
        $sesion_hacienda = false;
        $configuracion = null;

        if ($rol == 'admin') {
            $configuracion = Configuracion::firstWhere('user_id', $user->id);

            session()->put(
                [
                    'peso_servicio' => $configuracion->peso_servicio,
                    'dias_evento_notificacion' => $configuracion->dias_evento_notificacion,
                    'dias_diferencia_vacuna' => $configuracion->dias_diferencia_vacuna,
                ]
            );

            if ($user->haciendas->count() == 1) {
                $hacienda = $user->haciendas->first();
                session()->put('hacienda_id', $hacienda->id);
                event(new CrearSesionHacienda($hacienda));
                $sesion_hacienda = true;
            }
        } elseif ($rol == 'veterinario') {
            $usuario_veterinario = UsuarioVeterinario::where('user_id', $user->id)->first();
            $configuracion = Configuracion::firstWhere('user_id', $usuario_veterinario->admin_id);
            $hacienda = Hacienda::find($usuario_veterinario->veterinario->hacienda_id);
            session()->put(
                [
                    'peso_servicio' => $configuracion->peso_servicio,
                    'dias_evento_notificacion' => $configuracion->dias_evento_notificacion,
                    'dias_diferencia_vacuna' => $configuracion->dias_diferencia_vacuna,
                ]
            );

            /* verificar si el usuario veterinario esta trabajando en una unica hacienda */
            if ($usuario_veterinario->haciendas->count() == 1) {
                $hacienda = $usuario_veterinario->haciendas->first();
                session()->put('hacienda_id', $hacienda->id);
                event(new CrearSesionHacienda($hacienda));
                $sesion_hacienda = true;
            }
            
        }
        /* En caso que haya mas haciendas creadas se debera asignar manualmente en el contralador hacienda */

        return response()
            ->json(
                [
                    'login' =>
                    [
                        'id' => $user->id,
                        'usuario' => $user->usuario,
                        'rol' => $rol,
                        'token' => $user->createToken('API_TOKEN')->plainTextToken,
                        'sesion_hacienda' => $sesion_hacienda,
                        'configuracion' => new ConfiguracionResource($configuracion),
                    ]
                ],
                200
            );
    }
}
