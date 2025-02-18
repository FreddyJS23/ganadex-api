<?php

namespace App\Http\Requests;

use App\Models\Ganado;
use App\Rules\ComprobarVeterianario;
use App\Rules\VerificarGeneroToro;
use App\Rules\VerificarGeneroVaca;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StorePartoRequest extends FormRequest
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
            'observacion' => 'required|min:3|max:255',
            'nombre' => 'required|min:3|max:255|unique:ganados,nombre',
            'numero' => 'numeric|between:1,32767|unique:ganados,numero|nullable',
            'sexo' => 'required|in:H,M',
            'fecha' => 'date_format:Y-m-d',
            'peso_nacimiento' => 'numeric|between:1,32767',
            'personal_id' => ['required', new ComprobarVeterianario()]

        ];
    }

    public function after()
    {
        $idGanado = preg_replace("/[^0-9]/", "", (string) request()->path());
        $ganado = Ganado::firstWhere('id', $idGanado);
        //tiene que tener un servicio reciente para poder registrar un parto
        $servicioReciente=$ganado->servicioReciente;
        //tiene que estar en gestacion para poder registrar un parto
        $estadoGestacion=$ganado->estados()->where('estado','gestacion')->get()->toArray();



        return[
            function(Validator $validator) use ($servicioReciente, $estadoGestacion){
                if(!$servicioReciente){
                    $validator->errors()->add(
                        'servicio',
                        'Para registrar un parto la vaca debe de tener un servicio previo'
                    );
                }
                else if(!$estadoGestacion){
                    $validator->errors()->add(
                        'estado_gestacion',
                        'Para registrar un parto la vaca debe estar en gestacion'
                    );
                }
            }
        ];
    }
}
