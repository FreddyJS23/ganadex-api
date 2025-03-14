<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * @property-read ?int $id
 * @property string $nombre
 * @property-read ?User $user
 */
class Hacienda extends Model
{
    use HasFactory;

    protected $fillable = ['nombre'];

    /** Get the user that owns the Hacienda */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The veterinarios that belong to the Hacienda
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function veterinarios(): BelongsToMany
    {
        return $this->belongsToMany(Personal::class);
    }

    protected $hidden = ['pivot'];
}
