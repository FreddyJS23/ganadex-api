<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TodosRevisionesResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $existeRevision = $this->revisionReciente ? true : false;

        return[
        "id" => $this->id,
        "numero" => $this->numero,
        "pendiente"=> $this->pendiente,
        "estado"=>ucfirst($this->estado->estado),
        "ultima_revision" => $existeRevision ? $this->revisionReciente->fecha : 'Desconocido',
        "diagnostico" => $existeRevision ? $this->revisionReciente->tipoRevision->makeHidden('id','created_at','updated_at'): 'Desconocido',
        "proxima_revision" => $this->evento ? $this->evento->prox_revision : null ,
        "total_revisiones" => $this->revision_count
        ];
    }
}
