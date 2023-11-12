<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePersonalRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true ;
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
        $parametroPath = preg_replace("/[^0-9]/", "", request()->path());
       
        return [
            'ci'=>['required','numeric','digits_between:7,8',Rule::unique('personals')->ignore(intval($parametroPath))],
            'nombre'=>'required|string|min:3',
            'apellido'=>'required|string|min:3',
            'fecha_nacimiento'=>'required|date_format:Y-m-d',
            'cargo'=>'required',
            /* 'sueldo'=>'required|numeric', */
        ];
    }
}
