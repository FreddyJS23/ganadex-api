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
            'origen' => $this->ganado->origen->origen,
            'fecha_ingreso' => $this->ganado->fecha_ingreso,
            'fallecimiento' =>$this->ganado->fallecimiento ? (object)[
                'fecha' => $this->ganado->fallecimiento->fecha,
                'causa' => $this->ganado->fallecimiento->causa_fallecimiento->causa,
                'descripcion' => $this->ganado->fallecimiento->descripcion,
            ] : null,
            'venta' => $this->ganado->venta ? (object)[
                'fecha' => $this->ganado->venta->fecha,
                'comprador' => $this->ganado->venta->comprador->nombre,
            ] : null,
            'sexo' => $this->ganado->sexo,
            'tipo' => $this->ganado->tipo->tipo,
            'ganado_id' => $this->ganado->id,
            'estados' => $this->ganado->estados,
            'fecha_nacimiento' => $this->ganado->fecha_nacimiento,
            'pesos' => $this->ganado->peso
        ];
    }
}
