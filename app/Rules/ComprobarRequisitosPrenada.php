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
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $idGanado = preg_replace("/[^0-9]/", "", (string) request()->path());
        $ganado = Ganado::firstWhere('id', $idGanado);
        /* la vaca no deberia tener este estado ya que si se diagnistica
        que esta en gestacion es porque ya se le hizo un servicio nuevo,
        esto aseguraria que cada parto que se haga sea de un servicio diferente
        al igual si se hace un parto, la vaca deberia estar pendiente de servicio otra vez,
        esto con el fin de evitar poder registrar una revision con gestacion del mismo servicio*/
        $estadoPendienteServicio = $ganado->estados()->where('estado', 'pendiente_servicio')->get()->toArray();

        $pesoActualGanado = $ganado->peso->getRawOriginal('peso_actual');
        //tipo de validacion 1, al lo que es igual a una revision tipo gestacion
        if ($value == 1) {
            if ($pesoActualGanado < session('peso_servicio')) {
                $fail('La vaca debe tener un peso mayor a ' . session('peso_servicio') . ' kg');
            }
            if ($ganado->servicioReciente == null) {
                $fail('La vaca debe de tener un servicio previo');
            }
            if ($estadoPendienteServicio) {
                $fail('Realize un nuevo servicio, el servicio anterior ya se utilizo para el parto ya registrado');
            }
        }
    }
}
