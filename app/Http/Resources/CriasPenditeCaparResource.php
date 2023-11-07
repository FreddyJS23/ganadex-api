<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CriasPenditeCaparResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'nombre'=>$this->ganado->nombre,
            'fecha_nacimiento'=>$this->ganado->fecha_nacimiento,
        ];
    }
}
