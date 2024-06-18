<?php

namespace App\Policies;

use App\Models\GanadoDescarte;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class GanadoDescartePolicy
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
    public function view(User $user, GanadoDescarte $ganadoDescarte): bool
    {
        return $user->id === $ganadoDescarte->user_id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, GanadoDescarte $ganadoDescarte): bool
    {
        return $user->id === $ganadoDescarte->user_id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, GanadoDescarte $ganadoDescarte): bool
    {
        return true;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, GanadoDescarte $ganadoDescarte): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, GanadoDescarte $ganadoDescarte): bool
    {
        return false;
    }
}
