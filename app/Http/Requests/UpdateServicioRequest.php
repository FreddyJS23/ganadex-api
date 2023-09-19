<?php

namespace App\Http\Requests;

use App\Rules\VerificarGeneroToro;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateServicioRequest extends FormRequest
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
            'observacion' => 'required|min:3|max:255',
            'numero_toro' => ['required', Rule::exists('ganados','numero')->where(function ($query) {
                return $query->where('sexo', 'M');
            })],
            'tipo' => 'required|in:monta,inseminacion'
        ];
    }
}
