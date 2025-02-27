<?php

namespace App\Http\Requests;

use App\Rules\ComprobarVeterianario;
use App\Rules\VerificarGeneroToro;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreServicioRequest extends FormRequest
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
            'observacion' => 'required|min:3|max:255',
            'tipo' => 'required|in:monta,inseminacion',
            'fecha' => 'date_format:Y-m-d',
            'toro_id' => [
                Rule::requiredIf($this->tipo == 'monta'), Rule::exists('toros', 'id')
                    ->where(
                        fn($query) => $query->where('finca_id', session('finca_id'))
                    )
            ],
            'pajuela_toro_id' => [
                Rule::requiredIf($this->tipo == 'inseminacion'), Rule::exists('pajuela_toros', 'id')
                    ->where(
                        fn($query) => $query->where('finca_id', session('finca_id'))
                    )
            ],
        ];

          /* para evitar problema con la validacion de comprabacionVeterinario
        se agrega el campo solo si es un admin */
        $userAdmin = $this->user()->hasRole('admin');
        if ($userAdmin) {
            return $rules=array_merge($rules,['personal_id' => ['required_if:tipo,inseminacion', new ComprobarVeterianario()]]);
        } else return $rules;
    }
}
