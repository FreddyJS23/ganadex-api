<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Parto extends Model
{
    use HasFactory;

    protected $fillable = [
        'fecha',
        'observacion',
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

    /**
     * Get the toro that owns the Parto
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function toro(): BelongsTo
    {
        return $this->belongsTo(Toro::class);
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
}
