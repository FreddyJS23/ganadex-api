<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGanadoRequest extends FormRequest
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
        /**
         * Gets the route parameter.
         *
         * @return string
         */
        $parametroPath = preg_replace("/[^0-9]/", "", (string) request()->path());

        return [
            'nombre' => ['min:3','max:255', Rule::unique('ganados')->ignore(intval($parametroPath))],
            'numero' => ['numeric','between:1,32767', Rule::unique('ganados')->ignore(intval($parametroPath))],
            'origen_id' => 'required|integer|exists:origen_ganados,id',
            'tipo_id' => 'exists:ganado_tipos,id',
            'fecha_nacimiento' => 'date_format:Y-m-d',
            'peso_nacimiento' => 'numeric|between:1,32767',
            'peso_destete' => 'numeric|between:1,32767',
            'peso_2year' => 'numeric|between:1,32767',
            'peso_actual' => 'numeric|between:1,32767',

            'estado_id' => ['exclude', Rule::foreach(
                fn($value, $attrubute) => Rule::exists('estados', 'id')
            ),]

        ];
    }
}
