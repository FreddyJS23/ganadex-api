<?php

namespace App\Policies;

use App\Models\Insumo;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class InsumoPolicy
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
    public function view(User $user, Insumo $insumo): bool
    {
        return session('hacienda_id') === $insumo->hacienda->id ;
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
    public function update(User $user, Insumo $insumo): bool
    {
        return session('hacienda_id') === $insumo->hacienda->id && $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Insumo $insumo): bool
    {
        return session('hacienda_id') === $insumo->hacienda->id && $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Insumo $insumo): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Insumo $insumo): bool
    {
        return false;
    }
}
