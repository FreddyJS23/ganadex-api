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
        'intervalo_dosis',
        'dosis_recomendada_anual',
        'tipo_vacuna',
        'aplicable_a_todos'
    ];

    protected $casts = [
        'aplicable_a_todos' => 'boolean',
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

    /**
     * The tipoGanados that belong to the Vacuna
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function tiposGanado(): BelongsToMany
    {
        return $this->belongsToMany(GanadoTipo::class)
                    ->withPivot('sexo')
                    ->withTimestamps();
    }
}
