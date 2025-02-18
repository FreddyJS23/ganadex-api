<?php

namespace App\Policies;

use App\Models\CausasFallecimiento;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class CausasFallecimientoPolicy
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
    public function view(User $user, CausasFallecimiento $causasFallecimiento): bool
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
    public function update(User $user, CausasFallecimiento $causasFallecimiento): bool
    {
        return $user->hasRole('admin');

    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CausasFallecimiento $causasFallecimiento): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CausasFallecimiento $causasFallecimiento): bool
    {
        return false;

    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CausasFallecimiento $causasFallecimiento): bool
    {
        return false;

    }
}
