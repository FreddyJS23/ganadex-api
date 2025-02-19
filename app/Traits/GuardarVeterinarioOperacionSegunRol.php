<?php

namespace App\Traits;

use App\Models\UsuarioVeterinario;
use Illuminate\Foundation\Http\FormRequest;

trait GuardarVeterinarioOperacionSegunRol
{
    /**  determinar si el usuario es un admin de ser asi se guardara el veterinario(personal_id) que el haya elegido para la operacion
    caso que un veterinario haya iniciado sesion, todas las opereacion que haga se guardaran con su personal_id */
    protected function veterinarioOperacion(FormRequest $request): int
    {
        $user = $request->user();
        if ($user->hasRole('admin')) {
            return $request->input('personal_id');
        } else {
            $usuarioeterinario = UsuarioVeterinario::firstWhere('user_id', $user->id);
            return $usuarioeterinario->personal_id;
        }
    }
}
