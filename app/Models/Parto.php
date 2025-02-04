<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Parto extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha',
        'observacion',
        'fecha',
        'personal_id'
    ];

    /**
     * Get the ganado that owns the Parto
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class);
    }

    public function partoable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the ganado_cria that owns the Parto
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ganado_cria(): BelongsTo
    {
        return $this->belongsTo(Ganado::class, 'ganado_cria_id');
    }

    /**
     * Get the veterinario that owns the Parto
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }
}
