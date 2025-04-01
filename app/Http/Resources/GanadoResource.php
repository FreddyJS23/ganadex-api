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
            'id' => $this->id,
            'nombre' => $this->nombre,
            'numero' => $this->numero,
            'origen' => $this->origen->origen,
            'sexo' => $this->sexo,
            'tipo' => $this->tipo->tipo,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'fecha_ingreso' => $this->fecha_ingreso,
            'fallecimiento' =>$this->fallecimiento ? (object)[
                'fecha' => $this->fallecimiento->fecha,
                'causa' => $this->fallecimiento->causa_fallecimiento->causa,
                'descripcion' => $this->fallecimiento->descripcion,
            ] : null,
            'pesos' => $this->peso,
            'estados' => $this->estados,
            'eventos' => $this->evento,
        ];
    }
}
