<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    /**
     * Get all of the revisiones for the Personal
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function revisiones(): HasMany
    {
        return $this->hasMany(Revision::class);
    }
    /**
     * Get all of the servicios for the Personal
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function servicios(): HasMany
    {
        return $this->hasMany(Servicio::class);
    }
    /**
     * Get all of the partosAtendidos for the Personal
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function partosAtendidos(): HasMany
    {
        return $this->hasMany(Revision::class);
    }

    protected $hidden = ['created_at','updated_at','user_id','cargo_id'];

}
