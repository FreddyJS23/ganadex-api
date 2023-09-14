<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;



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
}
