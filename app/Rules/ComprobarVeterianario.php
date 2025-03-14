<?php

namespace App\Rules;

use App\Models\Personal;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ComprobarVeterianario implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        /* comprobar si el personal es veterinari y ademas que pertenece a la hacienda actual */
        if (!Personal::where('personals.id', $value)->where('cargo_id', 2)->whereRelation('haciendas','haciendas.id', session('hacienda_id'))->exists()) {
            $fail('El :attribute debe ser un veterinario de la hacienda actual');
        }
    }
}
