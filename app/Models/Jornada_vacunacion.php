<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Jornada_vacunacion extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha_inicio',
        'fecha_fin',
        'vacuna_id',
    ];

    /**
     * Get the vacuna that owns the Jornada_vacunacion
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vacuna(): BelongsTo
    {
        return $this->belongsTo(Vacuna::class);
    }

    /**
     * Get the user that owns the Jornada_vacunacion
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
