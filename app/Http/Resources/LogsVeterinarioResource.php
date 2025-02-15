<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LogsVeterinarioResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $fecha = Carbon::create($this->created_at)->locale('es');
        $fecha = "$fecha->dayName, " . $fecha->format('d-m-Y H:i:s');

        return [
            'id' => $this->id,
            'actividad' => $this->subject_type ?  class_basename($this->subject_type) : $this->log_name,
            'actividad_id' => $this->subject_id,
            'fecha' => $fecha,
        ];
    }
}
