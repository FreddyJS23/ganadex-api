<?php

namespace App\Http\Requests;

use App\Models\Ganado;
use App\Rules\ComprobarVeterianario;
use App\Rules\VerificarGeneroToro;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreServicioRequest extends FormRequest
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
        $rules = [
            'observacion' => 'required|min:3|max:255',
            'tipo' => 'required|in:monta,inseminacion',
            'fecha' => 'date_format:Y-m-d',
            'toro_id' => [
                Rule::requiredIf($this->tipo == 'monta'), Rule::exists('toros', 'id')
                    ->where(
                        fn($query) => $query->where('hacienda_id', session('hacienda_id'))
                    )
            ],
            'pajuela_toro_id' => [
                Rule::requiredIf($this->tipo == 'inseminacion'), Rule::exists('pajuela_toros', 'id')
                    ->where(
                        fn($query) => $query->where('hacienda_id', session('hacienda_id'))
                    )
            ],
        ];

          /* para evitar problema con la validacion de comprabacionVeterinario
        se agrega el campo solo si es un admin */
        $userAdmin = $this->user()->hasRole('admin');
        if ($userAdmin) {
            return $rules=array_merge($rules,['personal_id' => ['required_if:tipo,inseminacion', new ComprobarVeterianario()]]);
        } else return $rules;
    }

    public function after()
    {
        $idGanado = preg_replace("/[^0-9]/", "", (string) request()->path());
        $ganado = Ganado::firstWhere('id', $idGanado);
        //consultar si la vaca esta en gestacion
        $ganadoGestacion = Ganado::firstWhere('id', $idGanado)
        ->whereRelation('estados', 'estado', 'gestacion')
        ->count();

        /* una vaca en gestacion no debe permitirse regitrar un servicio */
        return[
            function(Validator $validator) use ($ganadoGestacion){
                if($ganadoGestacion == 1){
                    $validator->errors()->add(
                        'servicio',
                        'La vaca esta en gestación, si ocurrió un aborto registre una revision con con el diagnostico de "aborto"'
                    );
                }

            }
        ];
    }

}
