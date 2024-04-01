<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TiposNotificacion extends Model
{
    use HasFactory;

    /**
     * Get all of the notificaciones for the TiposNotifiacion
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class, 'tipo_id',);
    }

}
