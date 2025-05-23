<?php

namespace App\Http\Requests;

use App\Rules\ComprobarTienePesoActual;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVentaLoteRequest extends FormRequest
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
            'fecha' => 'required|date_format:Y-m-d',
            'ganado_ids' => 'required|array',
            'ganado_ids.*' => [
                'required', 'numeric', Rule::exists('ganados', 'id')
                    ->where(
                        fn($query) => $query->where('hacienda_id', session('hacienda_id'))
                    ),
                    new ComprobarTienePesoActual()
            ],
            'comprador_id' => [
                'required', 'numeric', Rule::exists('compradors', 'id')
                    ->where(
                        fn($query) => $query->where('hacienda_id', session('hacienda_id'))
                    )
            ],
        ];
    }
}
