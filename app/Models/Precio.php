<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Precio extends Model
{
    use HasFactory;

    protected $fillable = [
        'precio'
    ];

    /**
     * Get the user that owns the Precio
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class);
    }

    /**
     * Get all of the ventas_leche for the Precio
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function ventas_leche(): HasMany
    {
        return $this->hasMany(VentaLeche::class);
    }
}
