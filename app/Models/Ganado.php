<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Ganado extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'numero',
        'origen',
        'tipo_id',
        'sexo',
        'fecha_nacimiento',
    ];

/**
 * Get the tipo that owns the Ganado
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
public function tipo(): BelongsTo
{
    return $this->belongsTo(GanadoTipo::class);
}

/**
 * Get the toro associated with the Ganado
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasOne
 */
public function toro(): HasOne
{
    return $this->hasOne(Toro::class);
}

/**
 * Get the peso associated with the Ganado
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasOne
 */
public function peso(): HasOne
{
    return $this->hasOne(Peso::class);
}
/**
 * Get the estado associated with the Ganado
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasOne
 */
public function estado(): HasOne
{
    return $this->hasOne(Estado::class);
}

/**
 * Get all of the revision for the Ganado
 *
 * @return \Illuminate\Database\Eloquent\Relations\HasMany
 */
public function revision(): HasMany
{
    return $this->hasMany(Revision::class);
}

}
