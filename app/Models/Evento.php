<?php

namespace App\Models;

use App\Casts\Fecha;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Evento extends Model
{
    use HasFactory;

    /**
     * Get the ganado that owns the Evento
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class);
    }

    protected $casts=[
        'prox_revision' => Fecha::class,
        'prox_parto' => Fecha::class,
        'prox_secado' => Fecha::class,
    ];

    protected $hidden = ['ganado_id','created_at','updated_at'];
}
