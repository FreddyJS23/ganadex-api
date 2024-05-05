<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'peso_nacimiento' => 'max:10|regex:/^\d+(\.\d+)?KG$/',
            'peso_destete' => 'max:10|regex:/^\d+(\.\d+)?KG$/',
            'peso_2year' => 'max:10|regex:/^\d+(\.\d+)?KG$/',
            'peso_actual' => 'max:10|regex:/^\d+(\.\d+)?KG$/',
            'fecha_nacimiento'=>'date_format:Y-m-d'
        ];
    }
}
