<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreGanadoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|min:3|max:255|unique:ganados,nombre',
            'numero' => 'numeric|between:1,32767|unique:ganados,numero',
            'origen_id' => 'required|integer|exists:origen_ganados,id',
            'tipo_id' => 'required|integer|exists:ganado_tipos,id',
            'fecha_nacimiento' => 'date_format:Y-m-d',
            'fecha_ingreso' => ['date_format:Y-m-d', Rule::requiredIf(fn () => $this->origen_id == 2)], //origen externo
            'peso_nacimiento' => 'numeric|between:1,100',
            'peso_destete' => 'numeric|between:1,200',
            'peso_2year' => 'numeric|between:1,500',
            'peso_actual' => ['numeric','between:1,32767',Rule::requiredIf(fn ()=> in_array(5, $this->estado_id))],
            'estado_id' => Rule::foreach(
                fn($value, $attrubute) => Rule::exists('estados', 'id')
            ),
            //campos para registrar ganado vendido
            'fecha_venta' => ['date_format:Y-m-d', Rule::requiredIf(fn () => in_array(5, $this->estado_id))],
            'precio' => ['numeric', Rule::requiredIf(fn () => in_array(5, $this->estado_id))],
            'comprador_id' => [
                 Rule::requiredIf(fn () => in_array(5, $this->estado_id)),
                'numeric', Rule::exists('compradors', 'id')
                    ->where(
                        fn($query) => $query->where('hacienda_id', session('hacienda_id'))
                    )
            ],
            //campos para registrar ganado muerto
            'fecha_fallecimiento' => ['date_format:Y-m-d', Rule::requiredIf(fn () => in_array(2, $this->estado_id))],
            'causas_fallecimiento_id' => [Rule::requiredIf(fn () => in_array(2, $this->estado_id)), 'numeric', Rule::exists('causas_fallecimientos', 'id') ],
            'descripcion' => ['string','nullable','max:255', Rule::requiredIf(fn () => in_array(2, $this->estado_id))],
            //campos vacunacion
            'vacunas.*.fecha' => 'date_format:Y-m-d',
            'vacunas.*.prox_dosis' => 'date_format:Y-m-d',
            'vacunas.*.vacuna_id' => ['integer',Rule::exists('vacunas', 'id')],
        ];
    }
}
