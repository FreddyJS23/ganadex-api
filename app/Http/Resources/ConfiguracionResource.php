<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConfiguracionResource extends JsonResource
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
            'peso_servicio'=>$this->peso_servicio,
            'dias_evento_notificacion'=>$this->dias_evento_notificacion,
            'dias_diferencia_vacuna'=>$this->dias_diferencia_vacuna,
        ];
    }
}
