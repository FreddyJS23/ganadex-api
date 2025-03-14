<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class UsuarioVeterinario extends Model
{
    use HasFactory;

    protected $hidden = ['created_at', 'updated_at', 'personal_id', 'admin_id'];

    /**
     * Get the user that owns the UsuarioVeterinario
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the veterinario that owns the UsuarioVeterinario
     */
    public function veterinario(): BelongsTo
    {
        return $this->belongsTo(Personal::class, 'personal_id');
    }

    /* Get all of the haciendas for the UsuarioVeterinario */
    function haciendas(): BelongsToMany
    {
        return $this->veterinario->haciendas();
    }
}
