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
        if (!Personal::where('id', $value)->where('cargo_id', 2)->first()) {
            $fail('The :attribute must be veterinary');
        }
    }
}
