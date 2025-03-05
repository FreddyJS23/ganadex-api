<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartoCria extends Model
{
    use HasFactory;

    protected $fillable = ['observacion','ganado_id','parto_id'];

    /**
     * Get the Parto that owns the PartoCria
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parto(): BelongsTo
    {
        return $this->belongsTo(Parto::class);
    }

    /**
     * Get the ganado that owns the PartoCria
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class);
    }

    protected $hidden = ['parto_id'];
}

