<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RevisionResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'fecha' => $this->fecha,
            'revision' => $this->tipoRevision->makeHidden('id','created_at','updated_at'),
            'diagnostico' => $this->diagnostico,
            'tratamiento' => $this->tratamiento,
            'veterinario' => $this->veterinario,
            'vacuna' => $this->vacuna ? $this->vacuna->only('id', 'nombre') : null,
            'dosis' => $this->dosis ? $this->dosis . 'ml' : null,
        ];
    }
}
