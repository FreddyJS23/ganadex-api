<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PajuelaToro extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
    ];

    /**
     * Get the user that owns the PajuelaToro
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
