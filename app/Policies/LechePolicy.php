<?php

namespace App\Policies;

use App\Models\Leche;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class LechePolicy
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
    public function view(User $user, Leche $pesaje_leche): bool
    {
        return session('finca_id') === $pesaje_leche->finca->id;
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
    public function update(User $user, Leche $pesaje_leche): bool
    {
        return session('finca_id') === $pesaje_leche->finca->id && $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Leche $pesaje_leche): bool
    {
        return session('finca_id') === $pesaje_leche->finca->id && $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Leche $leche): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Leche $leche): bool
    {
        return false;
    }
}
