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

        if ($diferencia < 365) { return 1;
        }
        if ($diferencia >= 365 && $diferencia < 729) { return 2;
        }
        if ($diferencia >= 730 && $diferencia < 999) { return 3;
        }
        if ($diferencia >= 1000) { return 4;
        }
    }
}
