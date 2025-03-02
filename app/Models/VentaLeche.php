<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VentaLeche extends Model
{
    use HasFactory;

    protected $fillable = [
        'cantidad',
        'precio_id',
    ];

    /**
     * Get the user that owns the VentaLeche
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function hacienda(): BelongsTo
    {
        return $this->belongsTo(Hacienda::class);
    }

    /**
     * Get the precio that owns the VentaLeche
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function precio(): BelongsTo
    {
        return $this->belongsTo(Precio::class);
    }
}
