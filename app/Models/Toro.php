<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Toro extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'numero',
        'origen',
        'fecha_nacimiento',
    ];
    
    
    /**
     * Get the ganado that owns the Toro
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function ganado(): BelongsTo
    {
        return $this->belongsTo(Ganado::class);
    }

 /**
  * Get all of the servicios for the Toro
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphMany
  */
 public function servicios(): MorphMany
 {
     return $this->morphMany(Servicio::class, 'servicioable');
 }
 
 /**
  * Get all of the partos for the Toro
  *
  * @return \Illuminate\Database\Eloquent\Relations\MorphMany
  */
 public function padreEnpartos(): MorphMany
 {
     return $this->morphMany(Parto::class, 'partoable');
 }
    /**
     * Get the user that owns the Toro
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected $hidden = ['created_at', 'updated_at', 'user_id', 'ganado_id'];

}
