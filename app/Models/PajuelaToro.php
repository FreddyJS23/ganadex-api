<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;

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
    public function finca()
    {
        return $this->belongsTo(Finca::class);
    }

    /**
     * Get all of the servicios for the PajuelaToro
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function servicios(): MorphMany
    {
        return $this->morphMany(Servicio::class, 'servicioable');
    }

    /**
     * Get all of the partos for the Toro
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function partos(): MorphMany
    {
        return $this->morphMany(Parto::class, 'partoable');
    }

    protected $hidden = ['created_at', 'updated_at', 'user_id'];
}
