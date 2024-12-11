<?php

namespace App\Http\Requests;

use App\Rules\ComprobarVeterianario;
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
        return [
            'diagnostico'=>'required|min:3,|max:255',
            'tratamiento'=>'required|min:3,|max:255',
            'fecha' => 'date_format:Y-m-d',
            'personal_id'=>['required',new ComprobarVeterianario]
        ];
    }
}
