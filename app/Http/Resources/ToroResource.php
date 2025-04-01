<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ToroResource extends JsonResource
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
            'sexo' => $this->ganado->sexo,
            'tipo' => $this->ganado->tipo->tipo,
            'fecha_nacimiento' => $this->ganado->fecha_nacimiento,
            'fecha_ingreso' => $this->ganado->fecha_ingreso,
            'fallecimiento' =>$this->ganado->fallecimiento ? (object)[
                'fecha'=>$this->fallecimiento->fecha,
                'causas'=>$this->fallecimiento->causas_fallecimiento
            ] : null,
            'pesos' => $this->ganado->peso,
            'ganado_id' => $this->ganado->id,
            'estados' => $this->ganado->estados,
            'efectividad' => $this->efectividad,
            'padre_en_partos' => $this->padreEnPartos->count(),
            'servicios' => $this->servicios->count(),
        ];
    }
}
