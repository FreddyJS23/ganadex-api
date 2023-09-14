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
            'tipo'=>$this->tipo->tipo,
            'fecha_nacimiento'=>$this->fecha_nacimiento,
            'peso_nacimiento'=>$this->peso->peso_nacimiento,
            'peso_destete'=>$this->peso->peso_destete,
            'peso_2year'=>$this->peso->peso_2year,
            'peso_actual'=>$this->peso->peso_actual,
        ];
    }
}
