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
        
          $resource=  [
                "id" => $this->id,
                "numero" => $this->numero,
                "ultimo_servicio" => $existeServicio ? $this->servicioReciente->fecha : 'desconocido',
                "efectividad" => round($efectividad($this->parto_count, $this->servicios_count)),
                "total_servicios" => $this->servicios_count
            ];
        if ($existeServicio && $this->servicioReciente->servicioable_type == 'App\Models\Toro')
        $resource['toro'] = (object)
        [
            'id' => $this->servicioReciente->servicioable->id,
            'numero' => $this->servicioReciente->servicioable->ganado->numero
        ];
        elseif ($existeServicio && $this->servicioReciente->servicioable_type == 'App\Models\PajuelaToro')
        $resource['pajuela_toro'] = (object)
        [
            'id' => $this->servicioReciente->servicioable->id,
            'codigo' => $this->servicioReciente->servicioable->codigo
        ];

        return $resource;
    }
}
