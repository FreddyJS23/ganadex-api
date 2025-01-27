<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Models\User;
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

            $fincas=$user->fincas->count();

            //si solo existe una finca, se asigna esa a la sesion
            if($fincas == 1) session()->put('finca_id', $user->fincas->first()->id);
            /* En caso que haya mas fincas creadas se debera asignar manualmente en el contralador finca */

            $rol = $user->hasRole('admin') ? 'admin' : 'veterinario';

            return response()
                ->json(
                    [
                        'login' =>
                        [
                            'id' => $user->id,
                            'usuario' => $user->usuario,
                            'rol' => $rol,
                            'token' => $user->createToken('API_TOKEN')->plainTextToken,
                        ]
                    ],
                    200
                );
        } else  return response()->json(['message' => 'invalid password'], 401);
    }
}
