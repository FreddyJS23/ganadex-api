<?php

namespace App\Http\Requests;

use App\Rules\VerificarGeneroToro;
use App\Rules\VerificarGeneroVaca;
use Illuminate\Foundation\Http\FormRequest;

class UpdatePartoRequest extends FormRequest
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
            'observacion'=>'required|min:3|max:255',
        ];
    }
}
