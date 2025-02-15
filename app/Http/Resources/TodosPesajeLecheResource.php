<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodosPesajeLecheResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $pendientePesaje = new Carbon($this->pesajeLecheReciente->fecha);

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'numero' => $this->numero,
            'ultimo_pesaje' => $this->pesajeLecheReciente->peso_leche . "KG",
            'pesaje_este_mes' => $pendientePesaje->isCurrentMonth(),
        ];
    }
}
