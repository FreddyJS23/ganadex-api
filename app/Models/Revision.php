<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revision extends Model
{
    use HasFactory;

    protected $fillable=[
        'diagnostico',
        'tratamiento',
        'personal_id',
    ];

    /**
     * Get the ganado that owns the Revision
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class);
    }
}
