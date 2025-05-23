<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $usuario = [
            'id' => $this->id,
            'usuario' => $this->usuario,
            'email' => $this->email,
            'rol' => $this->getRoleNames()[0],
            'fecha_creacion' => $this->created_at->format('d-m-Y'),
            'tiene_preguntas_seguridad' => $this->tiene_preguntas_seguridad,
        ];

        $usuarioAdmin = array_merge(
            $usuario,
            [
            'haciendas' => $this->haciendas,
            'configuracion' => $this->configuracion
            ]
        );

        if ($this->hasRole('admin')) {
            return $usuarioAdmin;
        } else {
            return $usuario;
        }
    }
}
