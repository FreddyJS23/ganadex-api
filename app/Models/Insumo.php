<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Insumo extends Model
{
    use HasFactory;

    protected $fillable = [
        'insumo',
        'cantidad',
        'precio',
    ];

    /**
     * Get the user that owns the Insumo
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function finca(): BelongsTo
    {
        return $this->belongsTo(Finca::class);
    }
}
