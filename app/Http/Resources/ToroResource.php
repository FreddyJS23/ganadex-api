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
        $efectividad = fn (int $resultadoAlcanzado, int $resultadoPrevisto) => $resultadoAlcanzado * 100 / $resultadoPrevisto;
        return [
            'id'=>$this->id,
            'nombre'=>$this->ganado->nombre,
            'numero'=>$this->ganado->numero,
            'origen'=>$this->ganado->origen,
            'sexo'=>$this->ganado->sexo,
            'tipo'=>$this->ganado->tipo->tipo,
            'fecha_nacimiento'=>$this->ganado->fecha_nacimiento,
            'pesos' => $this->ganado->peso,
            'ganado_id' => $this->ganado->id,
            'estados' => $this->ganado->estados,
            'efectividad' => $this->padre_en_partos_count ? round($efectividad($this->padre_en_partos_count, $this->servicios_count), 2) : null,
            'padre_en_partos' => $this->padre_en_partos_count,
            'servicios' => $this->servicios_count,
        ];
    }
}
