<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartoResource extends JsonResource
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
            'cria'=>$this->ganado_cria,
            'padre_numero'=>$this->toro->ganado->numero,
        ];
    }
}
