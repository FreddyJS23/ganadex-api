<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreVentaLecheRequest extends FormRequest
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
            'cantidad' => 'required|numeric',
            'fecha' => 'date_format:Y-m-d',
            'precio_id' => ['required', Rule::exists('precios', 'id')
                ->where(
                    fn($query) => $query->where('hacienda_id', session('hacienda_id'))
                )]
        ];
    }
}
