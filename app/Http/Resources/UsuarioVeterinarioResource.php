<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UsuarioVeterinarioResource extends JsonResource
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
            'usuario' => $this->user->usuario,
            'nombre' => $this->veterinario->nombre,
            'fecha_creacion' => $this->created_at->format('d-m-Y'),
            'telefono' => $this->veterinario->telefono,
            'rol' => $this->user->getRoleNames()[0]

        ];
    }
}
