<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Configuracion extends Model
{
    use HasFactory;

    protected $fillable = [
        'peso_servicio',
        'dias_evento_notificacion',
        'dias_diferencia_vacuna',
    ];

    /**
     * Get the user that owns the Configuracion
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
