<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BalanceAnualLecheResource extends JsonResource
{
    public array $meses=["Enero","Febrero","Marzo","Abril","Mayo","Junio","Julio","Agosto","Septiembre","Octubre","Noviembre","Diciembre"];
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'mes'=>$this->meses[intval($this->mes - 1)],
            'promedio_pesaje_'=>round($this->promedio_pesaje,0)
        ];
    }
}
