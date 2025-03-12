<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PreguntasSeguridad extends Model
{
    use HasFactory;

    protected $table = 'preguntas_seguridad';

  /**
   * Get all of the respuestasSeguridad for the PreguntasSeguridad
   *
   * @return \Illuminate\Database\Eloquent\Relations\HasMany
   */
  public function respuestasSeguridad(): HasMany
  {
      return $this->hasMany(RespuestasSeguridad::class);
  }
}
