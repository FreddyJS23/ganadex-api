<?php

use Carbon\Carbon;

if (!function_exists('determinar_edad_res')) {
    /**
     * Obtener edad para asignar un tipo_id adecuado.
     *
     * @return int 1 | 2 | 3 | 4
     */
    function determinar_edad_res(string $fechaNacimiento): int
    {
        $fechaNacimiento = new Carbon($fechaNacimiento);
        $fechaActual = Carbon::now();

        $diferencia = $fechaNacimiento->diffInDays($fechaActual);

        if ($diferencia < 365) {
            return 1;
        }
        if ($diferencia >= 365 && $diferencia < 729) {
            return 2;
        }
        if ($diferencia >= 730 && $diferencia < 999) {
            return 3;
        }
        if ($diferencia >= 1000) {
            return 4;
        }
    }
}
if (!function_exists('determinar_genero_tipo_ganado')) {
    /**
     * Cambia el tipo de ganado a su forma femenina si el sexo es 'H'.
     *
     * @param Vacuna $vacuna
     * @return string
     */
    function determinar_genero_tipo_ganado($vacuna): string
    {
        $tipoGanadoVacunado = [];
        foreach ($vacuna->tiposGanado as $tipo) {
            $tipoGanado = $tipo->tipo;
            $sexo = $tipo->pivot->sexo;

            if ($sexo === 'H') {
                $tipoGanado = match ($tipo->tipo) {
                    'Becerro' => 'Becerra',
                    'Maute' => 'Mauta',
                    'Novillo' => 'Novilla',
                    'Adulto' => 'Adulta',
                    default => $tipo, // Mantener el tipo original si no hay un cambio
                };
            }
            array_push($tipoGanadoVacunado, $tipoGanado);
        }
        // Convertir el array a una cadena separada por comas
        $tipo = implode(',', $tipoGanadoVacunado);
        // Si la vacuna es aplicable a todos los ganados, agregar "Todos"
        if ($vacuna->aplicable_a_todos) {
            $tipo = "Todos";
        }
        // Devolver el tipo de ganado vacunado
        // Si el tipo es un string vacio, devolver "Todos"
        if (empty($tipo)) {
            return "Todos";
        }
        // Devolver el tipo de ganado vacunado
        return $tipo;
    }
}
