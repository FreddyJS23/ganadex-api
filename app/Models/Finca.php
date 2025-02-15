<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Finca extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre'
    ];

    /**
     * Get the user that owns the Finca
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
