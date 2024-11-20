<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Venta extends Model
{
    use HasFactory;

    protected $fillable=[
        //'precio',
        'ganado_id',
        'comprador_id'
    ];

    /**
     * Get the ganador that owns the Venta
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class);
    }

    /**
     * Get the comprador that owns the Venta
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function comprador(): BelongsTo
    {
        return $this->belongsTo(Comprador::class);
    }

    /**
     * Get the user that owns the Venta
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class);
    }

}
