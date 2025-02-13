<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogsEventosResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' =>$this->id,
            'operacion'=>$this->log_name,
            'descripcion'=>$this->description,
            'fecha'=>$this->created_at->format('d-m-Y H:i:s'),
        ];
    }
}
