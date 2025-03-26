<?php

namespace App\Http\Controllers;

use App\Models\RespuestasSeguridad;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\JsonResponse;

class RestablecerContraseñaController extends Controller
{
    private function verificarToken($token) : User | JsonResponse{
        $token = DB::table('password_reset_tokens')
                            ->where([
                            'token' => $token
                            ])
                            ->first();

        if($token == null) return response()->json(['message' => 'Token no encontrado'],403);

        //si hay mas de 15 minutos de diferencia entre la fecha actual y la fecha en la que se creo el token, se debe repetir el proceso
        if(now()->diffInMinutes($token->created_at) >= 15) return response()->json(['message' => 'Token expirado, porfavor vuelva a realizar la operacion'],403);

        $usuario=User::firstWhere('usuario', $token->usuario);

        return $usuario;
    }


    public function buscarUsuario(Request $request)
    {
        $request->validate([
            'usuario' => 'required|max:100',
        ]);

        $usuario = $request->input('usuario');

        $usuario = User::firstWhere('usuario', $usuario);

         //si el usuario no tiene preguntas de seguridad, no se puede restablecer la contraseña
         if(!$usuario){
            return response()->json(['message' => 'Usuario no encontrado'],404);
        }

         //vaciar tokens existente por si se intento restablecer la contraseña y no se haya culminado el proceso
        DB::table('password_reset_tokens')->where(['usuario'=> $usuario->usuario])->delete();


        //si el usuario no tiene preguntas de seguridad, no se puede restablecer la contraseña
        if($usuario->preguntasSeguridad->count()==0){
            return response()->json(['message' => 'El usuario no tiene preguntas de seguridad'],403);
        }

        $token = Str::random(64);

        //guardar token temporal que sera usado para restablecer la contraseña
        DB::table('password_reset_tokens')->insert([
            'usuario' => $usuario->usuario,
            'token' =>$token,
            'created_at' => now()
          ]);

        return response()->json(['token' => $token,'preguntas'=>$usuario->preguntasSeguridad], 200);
    }


    public function restablecerContraseña(Request $request, string $token)
    {

        $verificarToken = $this->verificarToken($token);

        //si el token tiene algun error se devuelve una respuesta con el error
        if($verificarToken instanceof JsonResponse)return $verificarToken;

        $usuario=$verificarToken;

        $request->validate([
            'password' => 'required|min:3|max:15',
            'respuestas' => 'required|array',
        ]);

        $respuestasForm = $request->input('respuestas');
        $password = $request->input('password');

        /* tener en cuenta que se estan recibiendo las respuestas en el orden que se estan mandando las preguntas,
        por ende se debe asegurar que el envio de las respuestas conincidan con el orden de las preguntas*/

        //verificar respuestas
        foreach ($usuario->respuestasSeguridad as $index => $respuesta) {
            /* convertir a minúsculas las respuestas ya que cuando se guardan o actualizan, las respuestas
            se almacenan en minúsculas para que a la hora de comparar con la respuesta cifrada no tenga
            distinción de la primera letra */

            $respuestaForm=strtolower($respuestasForm[$index]);

            //si la respuesta cifrada no coincide con la respuesta en la base de datos, devuelve error
            if (!Hash::check($respuestaForm, $respuesta->respuesta))
            return response()->json(['message' => 'respuestas incorrectas'],403);
    }

        $usuario->password = Hash::make($password);
        $usuario->save();

        //eliminar token
        DB::table('password_reset_tokens')
        ->where([
        'token' => $request->token
        ])
        ->delete();

        return response()->json(['message' => 'Contraseña restablecida exitosamente'],200);
    }
}
