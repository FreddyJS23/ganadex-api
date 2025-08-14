<?php

namespace App\Http\Requests;

use App\Models\Ganado;
use App\Rules\ComprobarVeterianario;
use App\Rules\ValidacionTipoRevision;
use Illuminate\Contracts\Validation\Validator;
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
        $rules = [
            'tratamiento' => [
                Rule::requiredIf(fn() => $this->requiresTratamiento()),
                'min:3',
                'max:255'
            ],
            'fecha' => 'date_format:Y-m-d',
            'observacion' => [
                Rule::requiredIf(fn() => $this->requiresObservacion()),
                'nullable',
                'string',
                'max:255'
            ],
            'vacuna_id' => [
                'nullable',
                'numeric',
                Rule::exists('vacunas', 'id')
            ],
            'dosis' => [
                'nullable',
                'numeric',
            ],
            'proxima'=>'date_format:Y-m-d|nullable',
            'servicio_desconocido' => [
                'boolean',
                'nullable',
            ],
            'dias_feto' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn() => $this->tipo_revision_id == 1 && $this->servicio_desconocido) // Solo requerido si es tipo de revisión "gestación" y es servicio desconocido
            ],
            'toro_id' => [
                Rule::requiredIf($this->tipo_revision_id == 1 && $this->servicio_desconocido), Rule::exists('toros', 'id')
                    ->where(
                        fn($query) => $query->where('hacienda_id', session('hacienda_id'))
                    )
            ],
        ];


        // Agregar validación de personal_id solo si el usuario es admin y se ya tiene un servicio reciente
        if ($this->user()->hasRole('admin') && !$this->servicio_desconocido) {
            $rules['personal_id'] = ['required', new ComprobarVeterianario()];
            $rules['tipo_revision_id']=[
                'required',
                'numeric',
                Rule::exists('tipo_revisions', 'id'),
                new ValidacionTipoRevision()
            ];
        }
        /* en caso de hacer una revision gestación y se necesita un servicio de emergencia  se quita la valicacion
        para el tipo de revision ya que pedira algun servicio reciente y no lo tendra,
        entonces para la revision de gestacion de emergencia
        se creara un servicio de emergencia en el controlador*/
        else {
            $rules['personal_id'] = ['required', new ComprobarVeterianario()];
            $rules['tipo_revision_id'] = [
                'required',
                'numeric',
                Rule::exists('tipo_revisions', 'id'),
            ];
        }

        return $rules;
    }

    /**
     * Determina si se requiere tratamiento.
     */
    private function requiresTratamiento(): bool
    {
        // Las siguientes revisiones no necesitan un tratamiento
        // 1: gestación, 2: descarte, 4: rutina
        return !in_array($this->tipo_revision_id, [1, 2, 4]);
    }

    /**
     * Determina si se requiere observación.
     */
    private function requiresObservacion(): bool
    {
        // Las siguientes revisiones necesitan una observación
        return in_array($this->tipo_revision_id, [1, 2, 3, 4]);
    }
}
