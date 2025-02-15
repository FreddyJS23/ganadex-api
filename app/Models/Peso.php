<?php

namespace App\Models;

use App\Casts\PesoGanadoCast;
use App\Casts\Pesos;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Peso extends Model
{
    use HasFactory;

    protected $fillable = [
        'peso_nacimiento',
        'peso_destete',
        'peso_2year',
        'peso_actual',
    ];
    /**
     * Get the ganado that owns the Peso
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class);
    }

    protected $casts = [
        'peso_nacimiento' => PesoGanadoCast::class,
        'peso_destete' => PesoGanadoCast::class,
        'peso_2year' => PesoGanadoCast::class,
        'peso_actual' => PesoGanadoCast::class,
    ];

    protected $hidden = ['ganado_id', 'created_at', 'updated_at'];
}
