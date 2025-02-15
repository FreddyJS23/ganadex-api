<?php

namespace App\Rules;

use App\Models\Ganado;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ComprobarTienePesoActual implements ValidationRule
{

    /**
     * Run the validation rule.
     *
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!Ganado::where('id', $value)->whereRelation('peso', 'peso_actual', '>', 0)->first()) {
            $fail('La vaca/ganado descarte/toro debe tener un peso actual');
        }
    }
}
