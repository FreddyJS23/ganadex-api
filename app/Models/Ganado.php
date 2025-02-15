<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
     * Get the ganadoDescarte associated with the Ganado
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function ganadoDescarte(): HasOne
    {
        return $this->hasOne(GanadoDescarte::class);
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
     * The estados that belong to the Ganado
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function estados(): BelongsToMany
    {
        return $this->belongsToMany(Estado::class);
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
     * Get the ganado most recent paro
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function partoReciente(): HasOne
    {
        return $this->hasOne(Parto::class)->latestOfMany();
    }
    /**
     * Get the ganado most recent revision
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function revisionReciente(): HasOne
    {
        return $this->hasOne(Revision::class)->latestOfMany();
    }


    /**
     * Get all of the servicios for the Ganado
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }

    /**
     * Get all of the comments for the Parto
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function parto(): HasMany
    {
        return $this->hasMany(Parto::class);
    }

    /**
     * Get all of the pesajes_leche for the Ganado
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function pesajes_leche(): HasMany
    {
        return $this->hasMany(Leche::class);
    }

    /**
     * Get the ganado most recent pesaje leche
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function pesajeLecheReciente(): HasOne
    {
        return $this->pesajes_leche()->one()->latestOfMany();
    }

    /**
     * Get the user that owns the Ganado
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class);
    }

    /**
     * Get the evento associated with the Ganado
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function evento(): HasOne
    {
        return $this->hasOne(Evento::class);
    }

    /**
     * Get the fallecimiento associated with the Ganado
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function fallecimiento(): HasOne
    {
        return $this->hasOne(Fallecimiento::class);
    }

    /**
     * Get all of the notificaciones for the Ganado
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notificaciones(): HasMany
    {
        return $this->hasMany(Notificacion::class);
    }

    /**
     * Get the fallecimiento associated with the Ganado
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function venta(): HasOne
    {
        return $this->hasOne(Venta::class);
    }

    /**
     * Get all of the vacunaciones for the Ganado
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vacunaciones(): HasMany
    {
        return $this->hasMany(Vacunacion::class);
    }
}
