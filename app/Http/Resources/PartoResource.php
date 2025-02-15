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
            'id'=>$this->id,
            'fecha'=>$this->fecha,
            'observacion'=>$this->observacion,
            'cria'=>$this->ganado_cria->makeHidden('tipo_id', 'user_id', 'created_at', 'updated_at')->load('peso:peso_nacimiento,ganado_id'),
            'veterinario' => $this->veterinario,
        ];

        if ($this->partoable_type == 'App\Models\Toro') {
            $resource['padre_toro'] = (object)
            [
            'id' => $this->partoable->id,
            'numero' => $this->partoable->ganado->numero
            ];
        } elseif ($this->partoable_type == 'App\Models\PajuelaToro') {
            $resource['pajuela_toro'] = (object)
            [
            'id' => $this->partoable->id,
            'codigo' => $this->partoable->codigo
            ];
        }

        return $resource;
    }
}
