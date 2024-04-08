<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodosPartosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */



    public function toArray(Request $request): array
    {

        $existeParto = $this->partoReciente ? true : false;
        return [
            'id' => $this->id,
            'numero' => $this->numero,
            "ultimo_parto" => $existeParto ? $this->partoReciente->fecha : 'desconocido',
            'total_partos' => $this->parto_count,
            'toro' => (object)([
                'id' => $this->partoReciente->toro->id,
                'numero' => $this->partoReciente->toro->ganado->numero
            ]),
            'cria' => (object)([
                'id' => $this->partoReciente->ganado_cria->id,
                'numero' => $this->partoReciente->ganado_cria->numero
            ])
        ];
    }
}
