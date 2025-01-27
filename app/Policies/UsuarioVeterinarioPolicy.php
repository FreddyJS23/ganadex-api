<?php

namespace App\Policies;

use App\Models\User;
use App\Models\UsuarioVeterinario;
use Illuminate\Auth\Access\Response;

class UsuarioVeterinarioPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UsuarioVeterinario $usuariosVeterinario): bool
    {
        return false;
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
    public function update(User $user, UsuarioVeterinario $usuariosVeterinario): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UsuarioVeterinario $usuariosVeterinario)
    {
        return $user->hasRole('admin') && $user->id == $usuariosVeterinario->admin_id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UsuarioVeterinario $usuariosVeterinario): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UsuarioVeterinario $usuariosVeterinario): bool
    {
        return false;
    }
}
