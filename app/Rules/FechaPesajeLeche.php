<?php

namespace App\Rules;

use App\Models\Ganado;
use App\Models\Leche;
use Carbon\Carbon;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class FechaPesajeLeche implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $fecha=Carbon::parse($value);

        $idGanado = preg_replace("/[^0-9]/", "", (string) request()->path());
        $ganado = Ganado::select('fecha_nacimiento')->firstWhere('id', $idGanado);

        $fechaNacimiento = Carbon::parse($ganado->fecha_nacimiento);

        //verificar si ya tiene un registro para el mes y el año
        //ya que los pesajes son uno al mes
        $leche = Leche::where('ganado_id', $idGanado)
            ->whereMonth('fecha', '=',$fecha->month )
            ->whereYear('fecha', '=',$fecha->year)
            ->first();

        if($leche != null){
        $fail('Esta vaca ya tiene un pesaje de leche para este mes');
    }
    else if($fecha > $fechaNacimiento){
        $fail('La fecha ingresada no es válida');
    }
    }
}
