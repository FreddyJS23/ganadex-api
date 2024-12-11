<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Servicio extends Model
{
    use HasFactory;

    protected $fillable = [
        'observacion',
        'tipo',
        'fecha',
        'personal_id',
    ];
    /**
     * Get the ganado that owns the Servicio
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class);
    }

    public function servicioable(): MorphTo
    {
        return $this->morphTo();
    }
    /**
     * Get the veterinario that owns the Servicio
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    protected function tipo(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) =>ucwords($value),
        );
    }
}
