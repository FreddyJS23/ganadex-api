<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Estado extends Model
{
    use HasFactory;

    protected $fillable=[
        'estado',
    ];

    /**
     * The ganados that belong to the Estado
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function ganados(): BelongsToMany
    {
        return $this->belongsToMany(Ganado::class);
    }

    protected $hidden = ['ganado_id', 'created_at', 'updated_at', 'pivot'];
}
