<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id'=>$this->id,
            'fecha'=>$this->fecha,
            'observacion'=>$this->observacion,
            'tipo'=>$this->tipo,
            'toro'=>$this->toro,
            'veterinario' => $this->veterinario,
        ];
    }
}
