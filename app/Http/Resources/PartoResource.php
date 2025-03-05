<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PartoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $resource = [
            'id' => $this->id,
            'fecha' => $this->fecha,
            'observacion' => $this->observacion,
            'crias' => $this->ganado_crias,
            'personal' => $this->personal,
        ];

        if ($this->partoable_type == \App\Models\Toro::class) {
            $resource['padre_toro'] = (object)
            [
            'id' => $this->partoable->id,
            'numero' => $this->partoable->ganado->numero
            ];
        } elseif ($this->partoable_type == \App\Models\PajuelaToro::class) {
            $resource['pajuela_toro'] = (object)
            [
            'id' => $this->partoable->id,
            'codigo' => $this->partoable->codigo
            ];
        }

        return $resource;
    }
}
