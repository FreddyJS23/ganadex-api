<?php

namespace App\Rules;

use App\Models\Ganado;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidacionTipoRevision implements ValidationRule
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

        /* ------------------------------ estados en bd ----------------------------- */
        //generados por el seeder estados
        /* se hace asi para no consultar la bd cada
        vez que se valida una revision, ya que los estados
        estan predefinidos en un seeder se hacen manual param mayor rendimiento*/
        $estadoServicioBD=['id'=>7,'estado'=>'pendiente_servicio'];
        $estadoGestacionBD=['id'=>3,'estado'=>'gestacion'];


        $estados = $ganado->estados()->get()->toArray();

        $estadoPendienteServicio = in_array($estadoServicioBD, $estados);
        $estadoGestacion = in_array($estadoGestacionBD, $estados);

        $pesoActualGanado = $ganado->peso->getRawOriginal('peso_actual');
        //validacion aplicada a una revision tipo gestacion
        if ($value == 1) {
            if ($pesoActualGanado < session('peso_servicio')) {
                $fail('La vaca debe tener un peso mayor a ' . session('peso_servicio') . ' kg');
            }
            if ($ganado->servicioReciente == null) {
                $fail('La vaca debe de tener un servicio previo');
            }
              /* la vaca no deberia tener estado pendiente servicio ya que si se diagnistica
                que esta en gestacion es porque ya se le hizo un servicio nuevo,
                esto aseguraria que cada parto que se haga sea de un servicio diferente
                al igual si se hace un parto, la vaca deberia estar pendiente de servicio otra vez,
                esto con el fin de evitar poder registrar una revision con gestacion del mismo servicio*/
            if ($estadoPendienteServicio) {
                $fail('Realize un nuevo servicio, el servicio anterior ya se utilizo para el parto ya registrado');
            }
        }
        //validacion aplicada a una revision tipo aborto
        if($value == 3){
            if(!$estadoGestacion){
                $fail('La vaca debe estar en gestación para poder realizar una revision aborto');
            }
        }
    }
}
