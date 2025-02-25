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
        //en caso de que no tenga pesaje leeche reciente quiere decir que no tiene una relacion en la tabla leche por ende seria la primera vez que se le haria un pesaje de leche
        $pendientePesaje =$this->pesajeLecheReciente ? new Carbon($this->pesajeLecheReciente->fecha) : false;

        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'numero' => $this->numero,
            'ultimo_pesaje' => $pendientePesaje ? $this->pesajeLecheReciente->peso_leche . "KG" : null,
            'pesaje_este_mes' =>$pendientePesaje ? $pendientePesaje->isCurrentMonth() : false,
        ];
    }
}
