<?php

namespace App\Policies;

use App\Models\Personal;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class PersonalPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Personal $personal): bool
    {
        return $user->id === $personal->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasRole('admin');
    }

    public function registrar_personal_hacienda(User $user): bool
    {
        return  $user->hasRole('admin');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Personal $personal): bool
    {
        return $user->id === $personal->user_id && $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Personal $personal): bool
    {
        return $user->id === $personal->user_id && $user->hasRole('admin');
    }

    public function eliminar_personal_hacienda(User $user, Personal $personal): bool
    {
        return $user->id === $personal->user_id && $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Personal $personal): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Personal $personal): bool
    {
        return false;
    }
}
