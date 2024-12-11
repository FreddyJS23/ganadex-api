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
            'causa' => 'required|min:3|max:255|string',
            'fecha' => 'date_format:Y-m-d',
            'ganado_id' => [
                'required', 'numeric', Rule::exists('ganados', 'id')
                ->where(
                    function ($query) {
                        return $query->where('finca_id', session('finca_id'));
                    }
                )
            ],
        ];
    }
}
