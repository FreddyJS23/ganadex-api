<?php

namespace App\Policies;

use App\Models\Hacienda;
use App\Models\User;
use App\Models\UsuarioVeterinario;
use Illuminate\Auth\Access\Response;

class HaciendaPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin') || $user->hasRole('veterinario');
    }

    public function crear_sesion_hacienda(User $user, Hacienda $hacienda): bool
    {
        if ($user->hasRole('admin')) {
            return $user->id == $hacienda->user_id;
        } elseif ($user->hasRole('veterinario')) {
            $usuario_veterinario = UsuarioVeterinario::where('admin_id', $user->id)->first();
            return $usuario_veterinario->admin_id == $hacienda->user_id
            && $usuario_veterinario->veterinario->hacienda_id ==  $hacienda->id;
        }
        return false;
    }

    public function verificar_sesion_hacienda(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function cambiar_hacienda_sesion(User $user): bool
    {
        if(session('hacienda_id')==null) return false;

        return $user->hasRole('admin') || $user->hasRole('veterinario');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Hacienda $hacienda): bool
    {
        return session('hacienda_id') === $hacienda->id ;
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
    public function update(User $user, Hacienda $hacienda): bool
    {
        return session('hacienda_id') === $hacienda->id && $user->id === $hacienda->user_id && $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Hacienda $hacienda): bool
    {
        return session('hacienda_id') === $hacienda->id && $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Hacienda $hacienda): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Hacienda $hacienda): bool
    {
        return false;
    }
}
