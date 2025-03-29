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
            'tipo_revision_id' => ['required', 'numeric', Rule::exists('tipo_revisions', 'id'), new ValidacionTipoRevision()],
            'tratamiento' => 'required|min:3,|max:255',
            'fecha' => 'date_format:Y-m-d',
        ];

        /* para evitar problema con la validacion de comprabacionVeterinario
        se agrega el campo solo si es un admin */
        $userAdmin = $this->user()->hasRole('admin');
        if ($userAdmin) {
            return $rules = array_merge($rules, ['personal_id' => ['required', new ComprobarVeterianario()]]);
        } else return $rules;
    }
}
