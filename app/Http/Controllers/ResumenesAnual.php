<?php

namespace App\Http\Controllers;

use App\Models\Ganado;
use Illuminate\Http\Request;

class ResumenesAnual extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function resumenNatalidad(Request $request)
    {
        $year = $request->query('year') ?? now()->format('Y');

         /* --------- informacion grafico poblacion de los ultimos 5 años y grafico natalida de los ultimos 5 años --------- */
        /**
         * obtener conteo nacimientos de los ultimos 5 años
         *
         * @var Collection<{año:int,partos_producidos:int}>
         */
        $nacimientosAnuales = Ganado::where('finca_id', session('finca_id'))
            ->selectRaw("DATE_FORMAT(fecha_nacimiento,'%Y') as año, COUNT(fecha_nacimiento) as partos_producidos")
            ->whereRaw("DATE_FORMAT(fecha_nacimiento,'%Y') >= ? ", [$year - 5])
            ->groupBy('año')
            ->get();

        /**
 * @var Array<int:string>  
*/
        $totalPoblacionAño = [];

        //obtener la poblacion de vacas sanas en cada año
        foreach ($nacimientosAnuales as $key => $value) {
            $cantidadVacasSanasAño = Ganado::selectRaw('COUNT(id) as total')
                ->where('finca_id', session('finca_id'))
                ->where('sexo', 'H')
                ->whereRelation('estados', 'estado', 'sano')
                ->whereRaw("DATE_FORMAT(fecha_nacimiento,'%Y') <= ? ", [$value['año']])
                ->first();
            //convertir año a string para poder usarlo como clave
            $año = strval($value['año']);
            //ir combinando los datos de los años anteriores
            //no se usa el metodo merge porque reinicia la clave primaria, que seria los año.
            //entonces para acceder al array se haria con las claves de los años
            $totalPoblacionAño = $totalPoblacionAño +  [$año => $cantidadVacasSanasAño['total']];
        }

        //formula tasa de natalidad
        $tasaNatalidad = fn(int $nacimientos, int $poblacion) => $nacimientos > 0 ? round($nacimientos / $poblacion * 100, 2) : 0;

        //agregar item rebaño y tasa de natalidad a la coleccion
        $nacimientosAnuales->transform(
            function ($item, int $key) use ($totalPoblacionAño, $tasaNatalidad) {
                //usar clave año para usar la variable global y obtener el valor de la clave qye sera el total de poblacion
                $item->poblacion = $totalPoblacionAño[$item['año']];
                $poblacion=$item['poblacion'];
                $partos_producidos=$item['partos_producidos'];

                //si no hay partos o poblacion no se calculará la tasa de natalidad
                if($poblacion==0 || $partos_producidos==0) {
                    $item->tasa_natalidad=0;
                    return $item;
                }

                $item->tasa_natalidad = $tasaNatalidad($partos_producidos, $poblacion);
                return $item;
            }
        );

        /* ------------------ grafico tora poblacion del año actual ----------------- */
        //obetner conteo agrupado anual ademas de la cantidad de machos y hembras para la poblacion
        $consultaSql = "DATE_FORMAT(fecha_nacimiento,'%Y') as año, COUNT(fecha_nacimiento) as total,
       COUNT(CASE WHEN sexo = 'M' THEN 1 END) as machos,
       COUNT(CASE WHEN sexo = 'H' THEN 1 END) as hembras";

        //obtener conteo agrupado de total de hembras y machos nacidos en el año
        $nacimientosAñoActual = Ganado::where('finca_id', session('finca_id'))
            ->selectRaw($consultaSql)
            ->whereYear('fecha_nacimiento', $year)
            ->groupBy('año')
            ->first();

        return response()->json(
            [
            'nacimientos_ultimos_5_año' => $nacimientosAnuales->toArray(),
            'nacimientos_año_actual' => $nacimientosAñoActual->toArray(),
            ]
        );
    }
}
