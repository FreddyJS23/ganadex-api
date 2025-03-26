<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RespuestasSeguridad extends Model
{
    use HasFactory;

    protected $table = 'respuestas_seguridad';

    protected $fillable = [
        'respuesta',
        'preguntas_seguridad_id',
    ];

    /**
     * Get the user that owns the RespuestasSeguridad
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the preguntas that owns the RespuestasSeguridad
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function preguntaSeguridad(): BelongsTo
    {
        return $this->belongsTo(PreguntasSeguridad::class, 'preguntas_seguridad_id');
    }
}
