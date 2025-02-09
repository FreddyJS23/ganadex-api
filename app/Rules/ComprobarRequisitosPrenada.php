<?php

namespace App\Rules;

use App\Models\Ganado;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ComprobarRequisitosPrenada implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $idGanado = preg_replace("/[^0-9]/", "", request()->path());
        $ganado = Ganado::firstWhere('id', $idGanado);

        $pesoActualGanado=$ganado->peso->getRawOriginal('peso_actual');
        if ($value == 'prenada') {
            if ($pesoActualGanado < session('peso_servicio')) {
                $fail('La vaca debe tener un peso mayor a ' . session('peso_servicio') . ' kg');
            }
            if ($ganado->servicioReciente == null) {
                $fail('La vaca debe de tener un servicio previo');
            }
        }
    }
}
