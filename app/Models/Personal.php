<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Personal extends Model
{
    use HasFactory;

    protected $fillable=[
        'ci',
        'nombre',
        'apellido',
        'fecha_nacimiento',
        'telefono',
        'cargo_id'
        /* 'sueldo', */
    ];

    /**
     * Get the user that owns the Personal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the cargo that owns the Personal
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cargo(): BelongsTo
    {
        return $this->belongsTo(Cargo::class);
    }
}
