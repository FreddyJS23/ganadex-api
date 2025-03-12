<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRespuestasSeguridadRequest extends FormRequest
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
            'preguntas' => 'required|array|min:3',
            'respuestas' => 'required|array|min:3',
            'preguntas.*' => ['integer',Rule::exists('preguntas_seguridad', 'id')],
            'respuestas.*' => 'required|string|max:255',
        ];
    }
}
