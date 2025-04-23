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
            'intervalo_dosis' => 'required|numeric|between:1,32767',
            'dosis_recomendada_anual' => 'nullable|integer|min:0',
            'tipo_vacuna' => ['required', Rule::in(['medica', 'plan_sanitario'])],
            'aplicable_a_todos' => 'boolean',
            'tipo_ganados' => [Rule::requiredIf(!$this->aplicable_a_todos)],
            'tipo_ganados.*.ganado_tipo_id' => 'required|exists:ganado_tipos,id',
            'tipo_ganados.*.sexo' => ['required', Rule::in(['H', 'M'])],
        ];
    }
}
