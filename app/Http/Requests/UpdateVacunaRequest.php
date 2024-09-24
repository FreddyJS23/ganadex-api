<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVacunaRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */ 
    public function rules(): array
    {
        return [
            'nombre' => 'required|min:3|max:255',
            'tipo_animal' => ['array', 'required', Rule::in(['rebano', 'becerro', 'maute', 'novillo', 'adulto'])],
            'intervalo_dosis' => 'required|between:1,32767',
        ];
    }
}
