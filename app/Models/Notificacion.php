<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\Attribute;

class Notificacion extends Model
{
    use HasFactory;

    protected $fillable=[
        'user_id',
        'tipo_id',
        'ganado_id',
        'dias_para_evento'
    ];

    /**
     * Get the ganado that owns the Revision
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class);
    }

    /**
     * Get the tipo that owns the Notificacion
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tipo(): BelongsTo
    {
        return $this->belongsTo(TiposNotificacion::class, 'tipo_id');
    }

    /**
     * Get the user that owns the Toro
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function leido(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => boolval($value),
        );
    }
    protected $hidden = ['ganado_id'];
}
