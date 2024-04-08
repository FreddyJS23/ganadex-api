<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodosServiciosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $efectividad = fn (int $resultadoAlcanzado, int $resultadoPrevisto) => $resultadoAlcanzado * 100 / $resultadoPrevisto;

        $existeServicio = $this->servicioReciente ? true : false;
        return
            [
                "id" => $this->id,
                "numero" => $this->numero,
                "ultimo_servicio" => $existeServicio ? $this->servicioReciente->fecha : 'desconocido',
                "toro" => $existeServicio ? (object)([
                    'id' => $this->servicioReciente->toro->id,
                    'numero' => $this->servicioReciente->toro->ganado->numero
                ]) : null,
                "efectividad" => round($efectividad($this->parto_count, $this->servicios_count)),
                "total_servicios" => $this->servicios_count
            ];
    }
}
