<?php

namespace App\Models;

use App\Casts\Fecha;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Leche extends Model
{
    use HasFactory;

    protected $fillable = [
        'peso_leche',
        'fecha'
    ];

    protected $casts=[
        'fecha' => Fecha::class,
    ];

    /**
     * Get the ganado that owns the Leche
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class);
    }

    /**
     * Get the user that owns the Leche
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function hacienda(): BelongsTo
    {
        return $this->belongsTo(Hacienda::class);
    }
}
