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
        $resource = [
            'id' => $this->id,
            'numero' => $this->numero,
            "ultimo_parto" => $existeParto ? $this->partoReciente->fecha : 'Sin parto',
            'total_partos' => $this->parto_count,
            'estado' => $this->estado,
            'cria' =>$existeParto ? (object)([
                'id' => $this->partoReciente->ganado_cria->ganado->id,
                'numero' => $this->partoReciente->ganado_cria->ganado->numero
            ]) : null,
        ];

        if ($existeParto && $this->partoReciente->partoable_type == \App\Models\Toro::class) {
            $resource['toro'] = (object)
            [
            'id' => $this->partoReciente->partoable->id,
            'numero' => $this->partoReciente->partoable->ganado->numero
            ];
        } elseif ($existeParto && $this->partoReciente->partoable_type == \App\Models\PajuelaToro::class) {
            $resource['pajuela_toro'] = (object)
            [
            'id' => $this->partoReciente->partoable->id,
            'codigo' => $this->partoReciente->partoable->codigo
            ];
        }

        return $resource;
    }
}
