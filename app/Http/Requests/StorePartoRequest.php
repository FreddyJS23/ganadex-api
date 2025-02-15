<?php

namespace App\Http\Requests;

use App\Rules\ComprobarVeterianario;
use App\Rules\VerificarGeneroToro;
use App\Rules\VerificarGeneroVaca;
use Illuminate\Foundation\Http\FormRequest;

class StorePartoRequest extends FormRequest
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
            'nombre' => 'required|min:3|max:255|unique:ganados,nombre',
            'numero' => 'numeric|between:1,32767|unique:ganados,numero|nullable',
            'sexo' => 'required|in:H,M',
            'fecha' => 'date_format:Y-m-d',
            'peso_nacimiento' => 'numeric|between:1,32767',
            'personal_id' => ['required', new ComprobarVeterianario()]

        ];
    }
}
