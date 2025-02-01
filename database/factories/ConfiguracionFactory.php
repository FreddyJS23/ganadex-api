<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Configuracion>
 */
class ConfiguracionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            /*Peso minimo para que este apta para un servicio  */
            'peso_servicio'=>330,
            /*Dias faltantes para crear una notificacion de evento proximo por ejemplo(evento proximo parto)*/
            'dias_evento_notificacion'=>10,
            /*Diferencia dias para que una vacuna se pueda posponer a una proxima jornada de vacunacion de la misma
            vacuna, por ejemplo, si la dosis de vacuna individual es el 10-01 y hay una jornada el 20-01 entonces la
            dosis de la vacuna individual se puede posponer hasta el 20-01 ya que la diferencia en dias es menor a 15
            esto con el fin de llevar un control de vacunas jornada vacunacion a todo el rebaño*/
            'dias_diferencia_vacuna'=>15,
        ];
    }
}
