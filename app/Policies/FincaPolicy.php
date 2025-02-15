<?php

namespace App\Policies;

use App\Models\Finca;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use Illuminate\Auth\Access\Response;

class FincaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function crear_sesion_finca(User $user, Finca $finca): bool
    {
        if ($user->hasRole('admin')) {
            return $user->id == $finca->user_id;
        } elseif ($user->hasRole('veterinario')) {
            $usuario_veterinario = UsuarioVeterinario::where('admin_id', $user->id)->first();
            return $usuario_veterinario->admin_id == $finca->user_id
            && $usuario_veterinario->veterinario->finca_id ==  $finca->id;
        }
    }

    public function verificar_sesion_finca(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Finca $finca): bool
    {
        return session('finca_id') === $finca->id ;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Finca $finca): bool
    {
        return session('finca_id') === $finca->id && $user->id === $finca->user_id && $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Finca $finca): bool
    {
        return session('finca_id') === $finca->id && $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Finca $finca): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Finca $finca): bool
    {
        return false;
    }
}
