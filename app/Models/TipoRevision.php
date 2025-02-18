<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TipoRevision extends Model
{
    use HasFactory;

    protected $fillable = ['tipo'];

    /**
     * Get all of the revisiones for the TipoRevision
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function revisiones(): HasMany
    {
        return $this->hasMany(Revision::class);
    }
}
