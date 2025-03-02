<?php

namespace App\Policies;

use App\Models\Plan_sanitario;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class Plan_sanitarioPolicy
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
    public function view(User $user, Plan_sanitario $planSanitario): bool
    {
        return session('finca_id') === $planSanitario->finca->id;
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
    public function update(User $user, Plan_sanitario $planSanitario): bool
    {
        return session('finca_id') === $planSanitario->finca->id && $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Plan_sanitario $planSanitario): bool
    {
        return session('finca_id') === $planSanitario->finca->id && $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Plan_sanitario $planSanitario): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Plan_sanitario $planSanitario): bool
    {
        return false;
    }
}
