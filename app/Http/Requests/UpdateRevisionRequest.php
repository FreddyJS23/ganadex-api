<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRevisionRequest extends FormRequest
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
            'tratamiento' => 'required|min:3,|max:255',
            'fecha' => 'date_format:Y-m-d',
            'diagnostico' => 'nullable|string|max:255',
            'dosis' => 'nullable|numeric',
        ];
    }
}
