<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Servicio extends Model
{
    use HasFactory;

    protected $fillable=[
        'observacion',
        'tipo'
    ];
    /**
     * Get the ganado that owns the Servicio
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class);
    }

    /**
     * Get the toro that owns the Servicio
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function toro(): BelongsTo
    {
        return $this->belongsTo(Toro::class);
    }
}
