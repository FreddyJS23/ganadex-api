<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ServicioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request)
    {
        $resource = [
            'id' => $this->id,
            'fecha' => $this->fecha,
            'observacion' => $this->observacion,
            'tipo' => $this->tipo,
            'veterinario' => $this->veterinario,
        ];

        if ($this->servicioable_type == 'App\Models\Toro') {
            $resource['toro'] = (object)
            [
                'id' => $this->servicioable->id,
                'numero' => $this->servicioable->ganado->numero
            ];
        } elseif ($this->servicioable_type == 'App\Models\PajuelaToro') {
            $resource['pajuela_toro'] = (object)
            [
                'id' => $this->servicioable->id,
                'codigo' => $this->servicioable->codigo
            ];
        }

        return $resource;
    }
}
