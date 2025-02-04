<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreToroRequest extends FormRequest
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
            'nombre'=>'required|min:3|max:255|unique:ganados,nombre',
            'numero'=>'required|numeric|between:1,32767|unique:ganados,numero',
            'origen'=>'min:3,|max:255',
            'peso_nacimiento' => 'numeric|between:1,32767',
            'peso_destete' => 'numeric|between:1,32767',
            'peso_2year' => 'numeric|between:1,32767',
            'peso_actual' => 'numeric|between:1,32767',
            'fecha_nacimiento'=>'date_format:Y-m-d',
            'estado_id' => Rule::foreach(function ($value, $attrubute) {
                return Rule::exists('estados', 'id');
            }),
        ];
    }
}
