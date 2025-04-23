<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VacunasResource extends JsonResource
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
            'intervalo_dosis' => $this->intervalo_dosis,
            'dosis_recomendada_anual' => $this->dosis_recomendada_anual,
            'tipo_vacuna' => $this->tipo_vacuna,
            'aplicable_a_todos' => $this->aplicable_a_todos,
            'tipos_ganado' =>count($this->tiposGanado) > 0 ? $this->tiposGanado->map(function ($tipoGanado) {
                return [
                    'id' => $tipoGanado->id,
                    'tipo' => $tipoGanado->tipo,
                    'sexo' => $tipoGanado->pivot->sexo,
                ];
            }) : null,
        ];
    }
}
