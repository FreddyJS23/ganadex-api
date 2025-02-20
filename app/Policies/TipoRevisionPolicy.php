<?php

namespace App\Policies;

use App\Models\TipoRevision;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TipoRevisionPolicy
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
    public function view(User $user, TipoRevision $tiposRevision): bool
    {
        return $user->hasRole('admin');
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
    public function update(User $user, TipoRevision $tiposRevision): bool
    {
        //no permitimos editar los tipos de revision predeterminadas
        if($tiposRevision->id == 1 || $tiposRevision->id == 2 || $tiposRevision->id == 3) return false;

    return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, TipoRevision $tiposRevision): bool
    {
        return $user->hasRole('admin');
     }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, TipoRevision $tiposRevision): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, TipoRevision $tiposRevision): bool
    {
        return false;
    }
}
