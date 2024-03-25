<?php

namespace App\Http\Requests;

use App\Models\Res;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateResRequest extends FormRequest
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
        /**
         * Gets the route parameter.
         *
         * @return string
         */
        $parametroPath = preg_replace("/[^0-9]/", "", request()->path());
        $ganadoId=Res::find($parametroPath)->ganado->id;
        return [
            'nombre'=>['required','min:3','max:255',Rule::unique('ganados')->ignore($ganadoId)],
            'numero'=>['required','numeric','between:1,32767',Rule::unique('ganados')->ignore($ganadoId)],
            'origen'=>'min:3,|max:255',
            'fecha_nacimiento'=>'date_format:Y-m-d'
        ];
    }
}
