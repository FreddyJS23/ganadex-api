<?php

namespace App\Policies;

use App\Models\Ganado;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RevisionPolicy
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
    public function view(User $user, Revision $revision): bool
    {
        return session('hacienda_id') === $revision->ganado->hacienda->id;
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
    public function update(User $user, Revision $revision): bool
    {
        return session('hacienda_id') === $revision->ganado->hacienda->id ;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Revision $revision): bool
    {
        return session('hacienda_id') === $revision->ganado->hacienda->id ;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Revision $revision): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Revision $revision): bool
    {
        return false;
    }
}
