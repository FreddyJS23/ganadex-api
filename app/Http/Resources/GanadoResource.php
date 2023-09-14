<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GanadoResource extends JsonResource
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
            'nombre'=>$this->nombre,
            'numero'=>$this->numero,
            'origen'=>$this->origen,
            'sexo'=>$this->sexo,
            'fecha_nacimiento'=>$this->fecha_nacimiento,
        ];
    }
}
