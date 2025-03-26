<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RespuestasSeguridadResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data=Carbon::create($this->updated_at);

        return [
            'id' => $this->id,
            'pregunta' => $this->pregunta,
            'pregunta_seguridad_id' => $this->pregunta_seguridad_id,
            'respuesta' => '********',
            'updated_at' => $data->format('d-m-Y'),
        ];
    }
}
