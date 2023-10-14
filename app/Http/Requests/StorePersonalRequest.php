<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePersonalRequest extends FormRequest
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
            'ci'=>'required|numeric|digits_between:7,8|unique:personals,ci',
            'nombre'=>'required|string|min:3',
            'apellido'=>'required|string|min:3',
            'fecha_nacimiento'=>'required|date_format:Y-m-d',
            'cargo'=> 'required|string|min:3',
            'sueldo'=>'required|numeric',
        ];
    }
}
