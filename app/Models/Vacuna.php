<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\AsArrayObject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vacuna extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'tipo_animal',
        'intervalo_dosis'
    ];

    protected $casts = [
        'tipo_animal' => AsArrayObject::class,
    ];

    /**
     * Get all of the vacunaciones for the Vacuna
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function vacunaciones(): HasMany
    {
        return $this->hasMany(Vacunacion::class);
    }

    /**
     * Get all of the planesSanitario for the Vacuna
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function planesSanitario(): HasMany
    {
        return $this->hasMany(Plan_sanitario::class);
    }
}
