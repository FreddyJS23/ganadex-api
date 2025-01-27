<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Finca extends Model
{
    use HasFactory;

    protected $fillable=[
        'nombre'
    ];

    /**
     * Get the user that owns the Finca
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    protected $hidden = ['ganado_id', 'created_at', 'updated_at','pivot'];
}
