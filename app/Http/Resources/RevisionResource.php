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
            'diagnostico' => $this->tipoRevision->makeHidden('id','created_at','updated_at'),
            'tratamiento' => $this->tratamiento,
            'veterinario' => $this->veterinario,
        ];
    }
}
