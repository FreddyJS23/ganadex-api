<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GanadoDescarteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nombre' => $this->ganado->nombre,
            'numero' => $this->ganado->numero,
            'origen' => $this->ganado->origen,
            'sexo' => $this->ganado->sexo,
            'tipo' => $this->ganado->tipo->tipo,
            'ganado_id'=>$this->ganado->id,
            'estados' => $this->ganado->estados,
            'fecha_nacimiento' => $this->ganado->fecha_nacimiento,
            'pesos'=>$this->ganado->peso
        ];
    }
}
