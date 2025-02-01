<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateConfiguracionRequest extends FormRequest
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
            'peso_servicio' => 'numeric|between:1,32767',
            'dias_diferencia_vacuna' => 'numeric|between:1,32767',
            'dias_evento_notificacion' => 'numeric|between:1,32767',
        ];
    }
}
