<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VentaResource extends JsonResource
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
            'fecha' => $this->fecha,
            'ganado' => $this->ganado->makeHidden('peso'),
            'peso' => $this->ganado->peso->peso_actual,
            //'precio'=>$this->precio,
            //'precio_kg'=>round($this->precio / intval($this->ganado->peso->peso_actual),2 ),
            'comprador' => $this->comprador->nombre,
        ];
    }
}
