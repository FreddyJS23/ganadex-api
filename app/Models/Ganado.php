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
 
    /**
     * Get the ganado most recent servicio
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function servicioReciente(): HasOne
    {
        return $this->hasOne(Servicio::class)->latestOfMany();
    }

    /**
     * Get all of the comments for the Parto
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function parto(): HasMany
    {
        return $this->hasMany(Parto::class, 'foreign_key', 'local_key');

    /**
     * Get all of the pesajes_leche for the Ganado
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pesajes_leche(): HasMany
    {
        return $this->hasMany(Leche::class);
    }
}
