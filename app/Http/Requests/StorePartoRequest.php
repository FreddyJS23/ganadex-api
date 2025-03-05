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
        $rules=[
            'observacion' => 'required|min:3|max:255',
            'fecha' => 'date_format:Y-m-d',
            /* campos crias del parto */
            'crias'=>'bail|required|array',
            'crias.*.nombre' => 'distinct|required|min:3|max:255|unique:ganados,nombre',
            'crias.*.observacion' => 'string|min:3|max:255',
            'crias.*.numero' => 'distinct|numeric|between:1,32767|unique:ganados,numero|nullable',
            'crias.*.sexo' => 'required|in:H,M,T',
            'crias.*.peso_nacimiento' => 'numeric|between:1,32767',
        ];

        /* para evitar problema con la validacion de comprabacionVeterinario
        se agrega el campo solo si es un admin */
        $userAdmin = $this->user()->hasRole('admin');
        if ($userAdmin) {
            return $rules=array_merge($rules,['personal_id' => ['required','numeric']]);
        } else return $rules;
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

    public function messages()
    {
       if($this->input('crias')) $multiplesCrias=count($this->input('crias')) > 1;
        else return ['crias'=>'El campo crias debe ser un arreglo'];
        /* Función para crear el mensaje de error, si son multiples crias se devuelve mensaje completo
        ejemplo: El campo nombre de la cria #1 es requerido.
        Caso contrario se devuelve el mensaje sin el segmento 2, ya que seria redundante
        numerar una sola cria
         */
        $mensaje=function ($segmento1,$segmento2,$segmento3) use($multiplesCrias)
        {
            if($multiplesCrias) return $segmento1.' '.$segmento2.' '.$segmento3;
            else return $segmento1.' '.$segmento3;
        };

        return [
            'crias.*.nombre.distinct' =>$mensaje( "El campo nombre", "de la cria #:position", "tiene un valor duplicado."),
            'crias.*.nombre.required' =>$mensaje( "El campo nombre", "de la cria #:position", "es requerido."),
            'crias.*.nombre.min' => $mensaje( "El campo nombre", "de la cria #:position", "debe tener al menos :min caracteres."),
            'crias.*.nombre.max' => $mensaje( "El campo nombre", "de la cria #:position", "debe ser menor que :max caracteres."),
            'crias.*.nombre.unique' => $mensaje( "El nombre", "de la cria #:position", "ya existe."),
            'crias.*.observacion.min' => $mensaje( "El campo observacion", "de la cria #:position", "debe tener al menos :min caracteres."),
            'crias.*.observacion.max' => $mensaje( "El campo observacion", "de la cria #:position", "debe ser menor que :max caracteres."),
            'crias.*.numero.distinct' => $mensaje( "El campo numero", "de la cria #:position", "tiene un valor duplicado."),
            'crias.*.numero.numeric' => $mensaje( "El campo numero", "de la cria #:position", "debe ser numerico."),
            'crias.*.numero.between' => $mensaje( "El campo numero", "de la cria #:position", "debe estar entre :min y :max."),
            'crias.*.numero.unique' => $mensaje( "El numero", "de la cria #:position", "ya existe."),
            'crias.*.sexo.required' => $mensaje( "El campo sexo", "de la cria #:position", "es requerido."),
            'crias.*.sexo.in' => $mensaje( "El campo sexo", "de la cria #:position", "debe ser H, M o T."),
            'crias.*.peso_nacimiento.numeric' => $mensaje( "El campo peso_nacimiento", "de la cria #:position", "debe ser numerico."),
            'crias.*.peso_nacimiento.between' => $mensaje( "El campo peso_nacimiento", "de la cria #:position", "debe estar entre :min y :max."),
        ];
    }
}
