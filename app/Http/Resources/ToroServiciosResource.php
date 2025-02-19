<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToroServiciosResource extends JsonResource
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
            'fecha'=>$this->fecha,
            'observacion'=>$this->observacion,
            'vaca'=>(object)[
                'id'=>$this->ganado->id,
                'numero'=>$this->ganado->numero,
            ],
            'veterinario'=>(object)[
                'id'=>$this->veterinario->id,
                'nombre'=>$this->veterinario->nombre,
            ],
        ];
    }
}
