<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Estado extends Model
{
    use HasFactory;

    protected $fillable=[
        'estado',
        'fecha_defuncion',
        'causa_defuncion',
    ];

/**
 * Get the ganado that owns the Estado
 *
 * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
 */
public function ganado(): BelongsTo
{
    return $this->belongsTo(Ganado::class);
}
}
