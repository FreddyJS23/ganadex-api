<?php

namespace App\Rules;

use App\Models\Personal;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class ComprobarPersonalHacienda implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $usuario = Auth::user();
        $personal = Personal::where('user_id', $usuario->id)->where('id', $value)->with('haciendas')->first();

        if($personal == null){
            $fail('El veterinario no existe');
        }

        $haciendas = $personal->haciendas;

        /* comprobar si el veterinario pertenece a la hacienda actual */
        foreach ($haciendas as $key => $value) {
            if($value->id == session('hacienda_id')){
                $fail('El veterinario ya pertenece a la hacienda actual');
                break;
        }
    }

    }
}
