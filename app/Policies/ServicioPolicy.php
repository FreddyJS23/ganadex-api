<?php

namespace App\Policies;

use App\Models\Servicio;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ServicioPolicy
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
    public function view(User $user, Servicio $servicio): bool
    {
        return session('hacienda_id') === $servicio->ganado->hacienda->id ;
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
    public function update(User $user, Servicio $servicio): bool
    {
        return session('hacienda_id') === $servicio->ganado->hacienda->id ;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Servicio $servicio): bool
    {
        return session('hacienda_id') === $servicio->ganado->hacienda->id ;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Servicio $servicio): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Servicio $servicio): bool
    {
        return false;
    }
}
