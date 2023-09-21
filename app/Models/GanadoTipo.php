<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class GanadoTipo extends Model
{
    use HasFactory;

    /**
     * Get the ganado associated with the GanadoTipo
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function ganado(): HasOne
    {
        return $this->hasOne(Ganado::class,'tipo_id');
    }
}
