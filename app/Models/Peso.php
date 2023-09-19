<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Peso extends Model
{
    use HasFactory;

    protected $fillable=[
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
}
