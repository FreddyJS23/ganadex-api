<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GanadoDescarte extends Model
{
    use HasFactory;

      protected $fillable = [
        'nombre',
        'numero',
        'origen_id',
        'fecha_nacimiento',
        'fecha_ingreso',
      ];

      /**
       * Get the ganado that owns the GanadoDescarte
       *
       * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
       */
      public function ganado(): BelongsTo
      {
          return $this->belongsTo(Ganado::class);
      }

      /**
       * Get the user that owns the Toro
       *
       * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
       */
      public function hacienda(): BelongsTo
      {
          return $this->belongsTo(Hacienda::class);
      }
}
