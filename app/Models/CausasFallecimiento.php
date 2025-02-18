<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CausasFallecimiento extends Model
{
    use HasFactory;

    protected $fillable = ['causa'];

    /**
     * Get all of the fallecimientos for the CausasFallecimiento
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fallecimientos(): HasMany
    {
        return $this->hasMany(Fallecimiento::class);
    }
}
