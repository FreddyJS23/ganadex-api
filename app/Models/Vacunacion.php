<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Vacunacion extends Model
{
    use HasFactory;

    protected $fillable = [
        'vacuna_id',
        'hacienda_id',
        'fecha',
        'prox_dosis',
    ];
    /**
     * Get the user that owns the Vacunacion
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function hacienda(): BelongsTo
    {
        return $this->belongsTo(Hacienda::class);
    }

    /**
     * Get the ganado that owns the Vacunacion
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class);
    }

    /**
     * Get the vacuna that owns the Vacunacion
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function vacuna(): BelongsTo
    {
        return $this->belongsTo(Vacuna::class);
    }
}
