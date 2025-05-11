<?php

namespace App\Http\Requests;

use App\Models\Ganado;
use App\Rules\ComprobarVeterianario;
use App\Rules\ValidacionTipoRevision;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRevisionRequest extends FormRequest
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
        $rules = [
            'tipo_revision_id' => [
                'required',
                'numeric',
                Rule::exists('tipo_revisions', 'id'),
                new ValidacionTipoRevision()
            ],
            'tratamiento' => [
                Rule::requiredIf(fn() => $this->requiresTratamiento()),
                'min:3',
                'max:255'
            ],
            'fecha' => 'date_format:Y-m-d',
            'observacion' => [
                Rule::requiredIf(fn() => $this->requiresObservacion()),
                'nullable',
                'string',
                'max:255'
            ],
            'vacuna_id' => [
                'nullable',
                'numeric',
                Rule::exists('vacunas', 'id')
            ],
            'dosis' => [
                'nullable',
                'numeric',
            ],
            'proxima'=>'date_format:Y-m-d|nullable'
        ];

        // Agregar validación de personal_id solo si el usuario es admin
        if ($this->user()->hasRole('admin')) {
            $rules['personal_id'] = ['required', new ComprobarVeterianario()];
        }

        return $rules;
    }

    /**
     * Determina si se requiere tratamiento.
     */
    private function requiresTratamiento(): bool
    {
        // Las siguientes revisiones no necesitan un tratamiento
        // 1: gestación, 2: descarte, 4: rutina
        return !in_array($this->tipo_revision_id, [1, 2, 4]);
    }

    /**
     * Determina si se requiere observación.
     */
    private function requiresObservacion(): bool
    {
        // Las siguientes revisiones necesitan una observación
        return in_array($this->tipo_revision_id, [1, 2, 3, 4]);
    }
}
