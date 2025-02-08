<?php

namespace App\Http\Controllers;

use App\Events\CrearSesionFinca;
use App\Http\Requests\LoginRequest;
use App\Http\Resources\ConfiguracionResource;
use App\Models\Configuracion;
use App\Models\Finca;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
        if (!$user) return response()->json(['message' => 'invalid user'], 401);


        //intentar autenticar
        if (Auth::attempt($request->only(['usuario', 'password']))) {
            $request->session()->regenerate();
            activity('Login')->log('Login');
            $rol = $user->hasRole('admin') ? 'admin' : 'veterinario';
            //notificar en el client si el login incluye inicio de sesion finca
            $sesion_finca=false;
            $configuracion=null;

            if($rol == 'admin'){
                $configuracion=Configuracion::firstWhere('user_id',$user->id);
                session()->put([
                    'peso_servicio'=>$configuracion->peso_servicio,
                    'dias_evento_notificacion'=>$configuracion->dias_evento_notificacion,
                    'dias_diferencia_vacuna'=>$configuracion->dias_diferencia_vacuna,
                ]);
                if($user->fincas->count() == 1){
                    $finca=$user->fincas->first();
                    session()->put('finca_id',$finca->id);
                    event(new CrearSesionFinca($finca));
                    $sesion_finca=true;
                }
            } else if($rol == 'veterinario'){
                $usuario_veterinario=UsuarioVeterinario::where('user_id',$user->id)->first();
                $configuracion=Configuracion::firstWhere('user_id',$usuario_veterinario->admin_id);
                $finca=Finca::find($usuario_veterinario->veterinario->finca_id)->first();
                session()->put([
                    'finca_id'=>$finca->id,
                    'peso_servicio'=>$configuracion->peso_servicio,
                    'dias_evento_notificacion'=>$configuracion->dias_evento_notificacion,
                    'dias_diferencia_vacuna'=>$configuracion->dias_diferencia_vacuna,
                ]);
                event(new CrearSesionFinca($finca));
                $sesion_finca=true;
            }
            /* En caso que haya mas fincas creadas se debera asignar manualmente en el contralador finca */


            return response()
                ->json(
                    [
                        'login' =>
                        [
                            'id' => $user->id,
                            'usuario' => $user->usuario,
                            'rol' => $rol,
                            'token' => $user->createToken('API_TOKEN')->plainTextToken,
                            'sesion_finca'=>$sesion_finca,
                            'configuracion'=>new ConfiguracionResource($configuracion),
                        ]
                    ],
                    200
                );
        } else  return response()->json(['message' => 'invalid password'], 401);
    }
}
