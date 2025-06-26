<?php

namespace App\Http\Requests;

use App\Models\Leche;
use App\Rules\FechaPesajeLeche;
use Illuminate\Foundation\Http\FormRequest;

class StoreLecheRequest extends FormRequest
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
            'peso_leche' => 'required|between:0.1,100|decimal:0,2',
            'fecha' => ['date_format:Y-m-d',new FechaPesajeLeche],
        ];
    }
}
