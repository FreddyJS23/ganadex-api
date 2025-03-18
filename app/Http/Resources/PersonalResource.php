<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PersonalResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
        "id" => $this->id,
        "ci" => $this->ci,
        "nombre" => $this->nombre,
        "apellido" => $this->apellido,
        "fecha_nacimiento" => $this->fecha_nacimiento,
        "telefono" => $this->telefono,
        "cargo" => $this->cargo->cargo,
        "haciendas"=> $this->haciendas->makeHidden(['user_id','created_at','updated_at']),
        /*  "sueldo"=> $this->sueldo, */
        ];
    }
}
