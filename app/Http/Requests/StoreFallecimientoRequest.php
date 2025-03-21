<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreFallecimientoRequest extends FormRequest
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
            'causas_fallecimiento_id' => ['required', 'numeric', Rule::exists('causas_fallecimientos', 'id') ],
            'descripcion' => 'min:3|max:255',
            'fecha' => 'date_format:Y-m-d',
            'ganado_id' => [
                'required', 'numeric', Rule::exists('ganados', 'id')
                    ->where(
                        fn($query) => $query->where('hacienda_id', session('hacienda_id'))
                    )
            ],
        ];
    }
}
