<?php

namespace App\Http\Requests;

use App\Models\Toro;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateToroRequest extends FormRequest
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
        /**
         * Gets the route parameter.
         *
         * @return string
         */
        $parametroPath = preg_replace("/[^0-9]/", "", (string) request()->path());
        $ganadoId = Toro::find($parametroPath)->ganado->id;

        return [
            'nombre' => ['min:3','max:255',Rule::unique('ganados')->ignore($ganadoId)],
            'numero' => ['numeric','between:1,32767',Rule::unique('ganados')->ignore($ganadoId)],
            'origen_id' => 'required|integer|exists:origen_ganados,id',
            'fecha_nacimiento' => 'date_format:Y-m-d',
            'peso_nacimiento' => 'numeric|between:1,32767',
            'peso_destete' => 'numeric|between:1,32767',
            'peso_2year' => 'numeric|between:1,32767',
            'peso_actual' => 'numeric|between:1,32767',
        ];
    }
}
