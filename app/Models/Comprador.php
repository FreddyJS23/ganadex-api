<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Comprador extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre'
    ];

    /**
     * Get the user that owns the Comprador
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class);
    }

    /**
     * Get all of the Ventas for the Comprador
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function Ventas(): HasMany
    {
        return $this->hasMany(Venta::class);
    }
}
