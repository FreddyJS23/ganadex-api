<?php

namespace App\Http\Controllers;

use App\Models\Fallecimiento;
use App\Models\Ganado;
use App\Models\GanadoTipo;
use App\Models\Hacienda;
use App\Models\Leche;
use App\Models\Parto;
use App\Models\Personal;
use App\Models\Peso;
use App\Models\Vacuna;
use App\Models\Venta;
use App\Models\VentaLeche;
use Barryvdh\DomPDF\Facade\Pdf;
use DateTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

    enum VistasReporte:string{
        case general='resumenGeneralReporte';
        case ventasLeche='resumenVentasLeche';
        case ventaGanadoAnual='resumenVentaGanadoAnual';
        case causasFallecimientos='resumenCausasFallecimientos';
        case natalidad='resumenNatalidad';
        case facturaVentaGanado='notaVentaGanado';
        case resumenVaca='ganadoReporte';
    }

class ReportsPdfController extends Controller
{

    public function obtenerNombreHacienda()
    {
        $nombre=Hacienda::firstWhere('id',session('hacienda_id'))->nombre;
        $nombre=strtoupper($nombre);
        return $nombre;
    }

    public function generarPdf(VistasReporte $vista,$dataPdf,$nombrePdf)
    {
        $nombreHacienda=$this->obtenerNombreHacienda();

        //agregar nombre a la data que se enviara a la vista
        $dataPdf['nombreHacienda']=$nombreHacienda;

        $pdf = Pdf::loadView($vista->value, $dataPdf);

        return $pdf->stream($nombrePdf .  " hacienda " . $nombreHacienda . ".pdf");
    }

    public function resumenGanado(Ganado $ganado)
    {

      /*  //descomentar para acceder a la ruta manualmente desde el navegador y debuguear
       //incustruar id para debug
        session(['hacienda_id' => 1]); */


        $ganado->load('peso', 'tipo', 'estados');
        $ganado->loadCount('servicios')->loadCount('revision')->loadCount('parto');
        $ultimaRevision = $ganado->revisionReciente;
        $ultimoServicio = $ganado->servicioReciente;
        $ultimoParto = $ganado->partoReciente;
        $ultimoPesajeLeche = $ganado->pesajeLecheReciente;
        $efectividad = fn (int $resultadoAlcanzado, int $resultadoPrevisto) => $resultadoAlcanzado * 100 / $resultadoPrevisto;

        $ganadoInfo = $ganado->only(['nombre', 'numero', 'sexo', 'fecha_nacimiento']);
        $ganadoInfo = array_merge($ganadoInfo, ['tipo' => $ganado->tipo->tipo]);
        $ganadoInfo = array_merge($ganadoInfo, ['origen' => $ganado->origen->origen]);
        $ganadoPeso = $ganado->peso ? Arr::except($ganado->peso->toArray(), ['id']) : [];

        $resumenServicio = [];
        if ($ultimoServicio) {
            //servicio monta
            if ($ultimoServicio->servicioable_type == \App\Models\Toro::class) {
                $resumenServicio = ['ultimo' => $ultimoServicio->fecha,
                'toro' => $ultimoServicio->servicioable->ganado->numero,
                'efectividad' => $ganado->parto_count ? round($efectividad($ganado->parto_count, $ganado->servicios_count), 2) : null,
                'total' => $ganado->servicios_count,
                ];
            }
            //servicio inseminacion
            if ($ultimoServicio->servicioable_type == \App\Models\PajuelaToro::class) {
                $resumenServicio = ['ultimo' => $ultimoServicio->fecha,
                'codigo pajuela' => $ultimoServicio->servicioable->codigo,
                'efectividad' => $ganado->parto_count ? round($efectividad($ganado->parto_count, $ganado->servicios_count), 2) : null,
                'total' => $ganado->servicios_count,
                ];
            }
        }

        //diferencia dias entre proxima vacunacion individual y plan sanitario
        $diferencia = 15;
        $setenciaDiferenciaDias = "DATEDIFF(MAX(plan_sanitarios.prox_dosis),MAX(vacunacions.prox_dosis))";

        /* Explicacion consulta
        usar un alias para las vacunas.
        primer case: para comprobar ver si alguna de las tablas relacionadas no existe registro,
        si existe registro en las dos se hace una suma de +1 ya que al contar los registros al existir en las dos tablas hace el conteo como 1.
        segundo case: determinar la proxima dosis dependiendo la existencia de registros en las tablas relacionadas,
        si existen registros en las dos tablas se comprueba que proxima dosis se le debe dar prioridad
        tercer case: determinar la ultima vacunacion dependiendo la existencia de registros en las tablas relacionadas,
        si existen registros en las dos tablas se comprueba que ultima dosis es la mas reciente*/
        $sentenciaSqlAgruparVacunas = "nombre as vacuna,
CASE
    WHEN MAX(vacunacions.prox_dosis) IS NULL OR MAX(plan_sanitarios.prox_dosis) IS NULL THEN COUNT(nombre)
    ELSE COUNT(nombre) + 1
    END as cantidad,
CASE
    WHEN MAX(vacunacions.prox_dosis) IS NULL THEN MAX(plan_sanitarios.prox_dosis)
    WHEN MAX(plan_sanitarios.prox_dosis) IS NULL THEN MAX(vacunacions.prox_dosis)
    WHEN $setenciaDiferenciaDias >= $diferencia THEN MAX(vacunacions.prox_dosis)
    ELSE MAX(plan_sanitarios.prox_dosis)
END as prox_dosis,
CASE
    WHEN MAX(vacunacions.fecha) IS NULL THEN MAX(plan_sanitarios.fecha_inicio)
    WHEN MAX(plan_sanitarios.fecha_inicio) IS NULL THEN MAX(vacunacions.fecha)
    WHEN MAX(plan_sanitarios.fecha_inicio) > MAX(vacunacions.fecha) THEN MAX(plan_sanitarios.fecha_inicio)
    ELSE MAX(vacunacions.fecha)
END as ultima_dosis
";

        /*  se utilizas el leftJoin para traer resultado independientemente si existen resultados en una tabla u otra,
        si se usa inner join se obtendra resultados precisos ya solo traera resultados cuando existan en las dos tablas relacionadas.
        Los ultimos dos wheres se utilizan para omitir los resultados de la tabla vacuna, ya que por defecto los trae y aumentaria el contador
        de aplicaciones de vacunas aplicada
        */
        $resumenVacunas = Vacuna::selectRaw($sentenciaSqlAgruparVacunas)
            ->leftJoin(
                'vacunacions',
                function (JoinClause $join) use ($ganado) {
                    $join->on('vacunas.id', '=', 'vacunacions.vacuna_id')
                        ->where('vacunacions.ganado_id', $ganado->id);
                }
            )
        ->leftJoin(
            'plan_sanitarios',
            function (JoinClause $join) use ($ganado) {
                $join->on('vacunas.id', '=', 'plan_sanitarios.vacuna_id')
                    ->where('plan_sanitarios.hacienda_id', session('hacienda_id'))
                    ->where('fecha_inicio', '>', $ganado->fecha_nacimiento ?? $ganado->created_at);
            }
        )
        ->where('plan_sanitarios.prox_dosis', '!=', 'null')
        ->orWhere('vacunacions.prox_dosis', '!=', 'null')
        ->groupBy('nombre')
        ->get()
        ->toArray();


        $resumenRevision = $ultimaRevision ? [
        'ultimo' => $ultimaRevision->fecha,
        'Diagnostico' => $ultimaRevision->diagnostico,
        'total' => $ganado->revision_count,
        ] : [];


        $resumenParto = $ultimoParto ? [
        'ultimo' => $ultimoParto->fecha,
        'peso_cria' => $ultimoParto->ganado_cria->ganado->peso->peso_nacimiento,
        'numero' => $ultimoParto->ganado_cria->numero ?: $ultimoParto->ganado_cria->ganado->nombre,
        'total' => $ganado->parto_count,
        ] : [];


        $estadoProduccionLeche = $ganado->estados->contains('estado', 'lactancia') ? "En producción" : 'Inactiva';

        $resumenPesajeLeche = $ultimoPesajeLeche ? [
        'ultimo' => $ultimoPesajeLeche->fecha,
        'peso' => $ultimoPesajeLeche->peso_leche,
        'estado' => $estadoProduccionLeche
        ] : [];


        $dataPdf = [
        'ganadoInfo' => $ganadoInfo,
        'ganadoPeso' => $ganadoPeso,
        'ganadoRevision' => $resumenRevision,
        'ganadoServicio' => $resumenServicio,
        'ganadoParto' => $resumenParto,
        'ganadoPesajeLeche' => $resumenPesajeLeche,
        'vacunas' => $resumenVacunas,
        ];


/*
      //descomentar para acceder a la ruta manualmente desde el navegador y debuguear
      $nombreHacienda=$this->obtenerNombreHacienda();
        $dataPdf['nombreHacienda']=$nombreHacienda;
        return view(VistasReporte::resumenVaca->value,$dataPdf); */
        return $this->generarPdf(VistasReporte::resumenVaca,$dataPdf,"Resumen vaca " . $ganado->numero);

    }


    public function resumenGeneral()
    {
       /*
       //descomentar para acceder a la ruta manualmente desde el navegador y debuguear
       //incustruar id para debug
        session(['hacienda_id' => 1]);
        */
        function obtenerSumaTotalPorTipo(array $tipo): int
        {
            $sumaTotalPorTipo = 0;
            foreach ($tipo as $key => $value) {
                $sumaTotalPorTipo += $value['cantidad'];
            }
            return $sumaTotalPorTipo;
        }


        $fechaActual = new DateTime();
        $mesActual = $fechaActual->format('m');

        /* ------------------------------ obtene vacas ------------------------------ */
        //tambien abarca las que seran futuras vacas
        $vacas = Ganado::where('hacienda_id', session('hacienda_id'))
            ->doesntHave('toro')
            ->doesntHave('ganadoDescarte')
            ->selectRaw('tipo, COUNT(tipo) as cantidad')
            ->join('ganado_tipos', 'tipo_id', 'ganado_tipos.id')
            ->orderBy('tipo_id')
            ->groupBy('tipo')
            ->get()
            ->toArray();

        $totalVacasEnProduccion = Ganado::select('id')->where('hacienda_id', session('hacienda_id'))
            ->whereRelation('estados', 'estado', 'lactancia')
            ->count();
        array_push($vacas, ['tipo' => 'total','cantidad' => obtenerSumaTotalPorTipo($vacas)]);
        array_push($vacas, ['tipo' => 'productiva','cantidad' => $totalVacasEnProduccion]);
        /* ------------------------------ obtene toros ------------------------------ */
        //tambien abacar los que seran futuros toros
        $toros
        = Ganado::where('hacienda_id', session('hacienda_id'))
            ->has('toro')
            ->doesntHave('ganadoDescarte')
            ->selectRaw('tipo, COUNT(tipo) as cantidad')
            ->join('ganado_tipos', 'tipo_id', 'ganado_tipos.id')
            ->orderBy('tipo_id')
            ->groupBy('tipo')
            ->get()
            ->toArray();

        //total de ganado toro
        array_push($toros, ['tipo' => 'total', 'cantidad' => obtenerSumaTotalPorTipo($toros)]);

        /* ------------------------------ obtene descartes ------------------------------ */
        $ganadoDescarte
        = Ganado::where('hacienda_id', session('hacienda_id'))
            ->doesntHave('toro')
            ->has('ganadoDescarte')
            ->selectRaw('tipo, COUNT(tipo) as cantidad')
            ->join('ganado_tipos', 'tipo_id', 'ganado_tipos.id')
            ->orderBy('tipo_id')
            ->groupBy('tipo')
            ->get()
            ->toArray();

        //total de descartes
        array_push($ganadoDescarte, ['tipo' => 'total', 'cantidad' => obtenerSumaTotalPorTipo($ganadoDescarte)]);

        /* --------------------- obtener natalidad y mortalidad --------------------- */
        $ganadoMortalida = Fallecimiento::selectRaw('COUNT(id) as total')->whereRelation('ganado', 'hacienda_id', session('hacienda_id'))
            ->whereYear('fecha', $fechaActual->format('Y'))
            ->first()
            ->total;

        $ganadoNatalidad = Ganado::selectRaw('COUNT(id) as total')
            ->whereYear('fecha_nacimiento', $fechaActual->format('Y'))
            ->where('hacienda_id', session('hacienda_id'))
            ->first()->total;

        /* ------------------------ obtener top vacas productoras y menos productoras ------------------------- */
        $topVacasProductoras = Leche::withWhereHas(
            'ganado',
            function ($query) {
                $query->where('hacienda_id', session('hacienda_id'))
                    ->select('id', 'numero');
            }
        )->orderBy('peso_leche', 'desc')
        ->whereMonth('fecha', $mesActual)
        ->select('peso_leche', 'ganado_id')
        ->limit(3)
        ->get();

        $ordernarArrayVacasProductoras = [];
        foreach ($topVacasProductoras as $key => $vaca) {
            array_push(
                $ordernarArrayVacasProductoras,
                [
                'numero' => $vaca->ganado->numero,
                'peso_leche' => $vaca->peso_leche
                ]
            );
            ;
        }
        $topVacasProductoras = $ordernarArrayVacasProductoras;

        $ordernarArrayVacasMenosProductoras = [];
        $topVacasMenosProductoras = Leche::withWhereHas(
            'ganado',
            function ($query) {
                $query->where('hacienda_id', session('hacienda_id'))->select('id', 'numero');
            }
        )->orderBy('peso_leche', 'asc')
        ->whereMonth('fecha', $mesActual)
        ->limit(3)
        ->get();

        foreach ($topVacasMenosProductoras as $key => $vaca) {
            array_push(
                $ordernarArrayVacasMenosProductoras,
                [
                'numero' => $vaca->ganado->numero,
                'peso_leche' => $vaca->peso_leche
                ]
            );
        }
        $topVacasMenosProductoras = $ordernarArrayVacasMenosProductoras;

        /* ------------------------ obtener total vacas en gestacio,revision y servicio ------------------------- */
        $totalVacasEnGestacion = Ganado::where('hacienda_id', session('hacienda_id'))
            ->whereRelation('estados', 'estado', 'gestacion')
            ->count();

        $totalGanadoPendienteRevision = Ganado::where('hacienda_id', session('hacienda_id'))
            ->whereRelation('estados', 'estado', 'pendiente_revision')
            ->count();

        $novillasAmontar = Ganado::where('hacienda_id', session('hacienda_id'))
            ->whereRelation('estados', 'estado', 'pendiente_servicio')
            ->count();

        $ganadoPendienteAcciones = [
        'revision' => $totalGanadoPendienteRevision,
        'servir' => $novillasAmontar,
        'preñadas' => $totalVacasEnGestacion,
        ];

        /* ------------------------ obtener total personal ------------------------- */
        $totalPersonal = Personal::whereRelation('haciendas','haciendas.id', session('hacienda_id'))
            ->selectRaw('cargo, COUNT(cargo) as cantidad')
            ->join('cargos', 'cargo_id', 'cargos.id')
            ->groupBy('cargo')
            ->get()
            ->toArray();

        $personal = [];
        $cantidadTotalPersonal = 0;
        foreach ($totalPersonal as $key => $value) {
            $cantidadTotalPersonal += $value['cantidad'];
            $personal = array_merge($personal, [$value['cargo'] => $value['cantidad']]);
        }
        $personal = array_merge($personal, ['total' => $cantidadTotalPersonal]);
        /* ------------------------ obtener balance anual de leche ------------------------- */
        $balanceAnualLeche = Leche::selectRaw("DATE_FORMAT(fecha,'%m') as mes")
            ->selectRaw("AVG(peso_leche) as promedio_pesaje")
            ->groupBy('mes')
            ->orderBy('mes', 'asc')
            ->whereYear('fecha', now()->format('Y'))
            ->get()
            ->toArray();

        $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];

        $balancePrimerSemestre = [];
        $balanceSegundoSemestre = [];

        foreach ($balanceAnualLeche as $key => $arrayInfoMes) {
            $mesPrimerSemestre = true;
            intval($arrayInfoMes['mes']) <= 5 ? $mesPrimerSemestre : $mesPrimerSemestre = false;
            $mes = $meses[intval($arrayInfoMes['mes'])];
            $promedioPesaje = round($arrayInfoMes['promedio_pesaje']);
            //$balanceAnualLeche[$key]=['mes'=> $mes,'promedio_pesaje'=>$promedioPesaje];
            /**
     * @var array{'mes':string,'promedio_pesaje':float'}
*/
            $infoMesFormateado = ['mes' => $mes, 'promedio_pesaje' => $promedioPesaje];

            $mesPrimerSemestre ? array_push($balancePrimerSemestre, $infoMesFormateado) : array_push($balanceSegundoSemestre, $infoMesFormateado);
        }

        /* -------------------- relleno de datos para el reporte -------------------- */
        $dataPdf = [
        'vacas' => $vacas,
        'toros' => $toros,
        'ganadoDescarte' => $ganadoDescarte,
        'natalidad' => strval($ganadoNatalidad),
        'mortalidad' => strval($ganadoMortalida),
        'topVacasProductoras' => $topVacasProductoras,
        'topVacasMenosProductoras' => $topVacasMenosProductoras,
        'ganadoPendienteAcciones' => $ganadoPendienteAcciones,
        'totalPersonal' => $personal,
        'balancePrimerSemestre' => $balancePrimerSemestre,
        'balanceSegundoSemestre' => $balanceSegundoSemestre
        ];

      /*
      //descomentar para acceder a la ruta manualmente desde el navegador y debuguear
      $nombreHacienda=$this->obtenerNombreHacienda();
        $dataPdf['nombreHacienda']=$nombreHacienda;
        return view(VistasReporte::general->value,$dataPdf); */

        return $this->generarPdf(VistasReporte::general,$dataPdf,"Reporte general " . $fechaActual->format('d-m-Y'));

    }

    public function resumenVentasLeche(Request $request)
    {
        $inicio = $request->query('start');
        $fin = $request->query('end');

        $ventasLeche = VentaLeche::where('hacienda_id', session('hacienda_id'))
            ->oldest('fecha')
            ->select('venta_leches.fecha', 'cantidad', 'precio')
            ->selectRaw('(cantidad * precio) AS ganancia_total')
            ->join('precios', 'precio_id', 'precios.id')
            ->whereBetween('venta_leches.fecha', [$inicio, $fin])
            ->get()
            ->toArray();

        $dataPdf = ['ventasLeche' => $ventasLeche,
        'inicio' => $inicio,
        'fin' => $fin,];

        return $this->generarPdf(VistasReporte::ventasLeche,$dataPdf,"Reporte venta de leche " . $inicio . " - " . $fin);
    }


    public function resumenVentaGanadoAnual(Request $request)
    {


       //descomentar para acceder a la ruta manualmente desde el navegador y debuguear
       //incustruar id para debug
        session(['hacienda_id' => 1]);


        $regexYear = "/^[2][0-9][0-9][0-9]$/";

        $year = preg_match($regexYear, $request->query('year')) ? $request->query('year') : now()->format('Y');

        $ventasGanado = Venta::where('ventas.hacienda_id', session('hacienda_id'))
            ->join('ganados', 'ganado_id', 'ganados.id')
        //->selectRaw("DATE_FORMAT(fecha,'%m') as mes,numero,precio")
            ->selectRaw("DATE_FORMAT(fecha,'%m') as mes,numero")
            ->orderBy('mes', 'asc')
            ->whereYear('fecha', $year)
            ->get();

        $ventasGanado->transform(
            function (Venta $item, int $key) {
                $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                $item->mes = $meses[intval($item->mes)];
                return $item;
            }
        );

        $dataPdf = ['ventasGanado' => $ventasGanado->groupBy('mes')->toArray(),'year' => $year];


   /*    //descomentar para acceder a la ruta manualmente desde el navegador y debuguear
      $nombreHacienda=$this->obtenerNombreHacienda();
        $dataPdf['nombreHacienda']=$nombreHacienda;
        //return view(VistasReporte::ventaGanadoAnual->value,$dataPdf);
 */
        return $this->generarPdf(VistasReporte::ventaGanadoAnual,$dataPdf,"Resumen ventas de animales " . "año " . $year);
    }

    public function resumenCausasFallecimientos(Request $request)
    {

        $inicio = $request->query('start');
        $fin = $request->query('end');

        $fallecimientos = Fallecimiento::whereRelation('ganado', 'hacienda_id', session('hacienda_id'))
            ->join('causas_fallecimientos','causas_fallecimiento_id','=','causas_fallecimientos.id')
            ->selectRaw('causa, COUNT(causa) AS cantidad')
            ->orderby('cantidad', 'desc')
            ->groupBy('causa')
            ->whereBetween('fallecimientos.fecha', [$inicio, $fin])
            ->get();

        $cantidadOtrasCausas = 0;

        foreach ($fallecimientos as $key => $item) {
            $key > 3 && $cantidadOtrasCausas += $item->cantidad;
        };

        $fallecimientos = $fallecimientos->filter(
            function (Fallecimiento $item, int $key) {

                if ($key < 3) {
                    return $item;
                }
            }
        )->toArray();

        array_push($fallecimientos, ['causa' => 'otras_causas', 'cantidad' => $cantidadOtrasCausas]);

        $dataPdf = ['causasFallecimientos' => $fallecimientos];

        return $this->generarPdf(VistasReporte::causasFallecimientos,$dataPdf,"Resumen causas de fallecimientos " . $inicio . " - " . $fin);

    }

    public function resumenNatalidad(Request $request)
    {

        $regexYear = "/^[2][0-9][0-9][0-9]$/";

        $year = preg_match($regexYear, $request->query('year')) ? $request->query('year') : now()->format('Y');
        // Recibir las imágenes en base64 desde la solicitud
        //eliminar comillas ya que viene con comilas por el formdata, para poder usarlo en el src de la etiqueta html img
        $graficoTorta = str_replace("'", "", $request->input('graficoTorta'));
        $graficoLineal = str_replace("'", "", $request->input('graficoLineal'));
        $graficoBarra = str_replace("'", "", $request->input('graficoBarra'));

        //obetner conteo agrupado por mes ademas de la cantidad de machos y hembras en ese mes
        $consultaSql = "DATE_FORMAT(fecha_nacimiento,'%m') as mes, COUNT(fecha_nacimiento) as total,
        COUNT(CASE WHEN sexo = 'M' THEN 1 END) as machos,
        COUNT(CASE WHEN sexo = 'H' THEN 1 END) as hembras";

        //obtener conteo agrupado por mes ademas de la cantidad de machos y hembras en ese mes
        $nacimientosPorMeses = Ganado::where('hacienda_id', session('hacienda_id'))
            ->selectRaw($consultaSql)
            ->orderBy('mes', 'asc')
            ->groupBy('mes')
            ->whereYear('fecha_nacimiento', $year)
            ->get();


        //transformar la coleccion para obtener el mes en formato texto
        $nacimientosPorMeses->transform(
            function (Ganado $item, int $key) {
                $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
                $item->mes = $meses[intval($item->mes)];
                return $item;
            }
        );


        $dataPdf = [
        'nacimientosPorMeses' => $nacimientosPorMeses->toArray(),
        'graficoTorta' => $graficoTorta,
        'graficoLineal' => $graficoLineal,
        'graficoBarra' => $graficoBarra,
        'year' => $year
        ];

        return $this->generarPdf(VistasReporte::natalidad,$dataPdf,"Resumen natalidad año $year");
    }

    public function facturaVentaGanado()
    {


        $ventaGanado = Venta::where('hacienda_id', session('hacienda_id'))
            ->with(['ganado' => ['peso'],'comprador'])
            ->orderBy('fecha', 'desc')
            ->first();

        $ganado = $ventaGanado->ganado;

        $sentenciaSql = "nombre as vacuna,
    CASE
        WHEN vacunacions.fecha  IS NULL THEN plan_sanitarios.fecha_inicio
        ELSE vacunacions.fecha
        END as fecha";
        /*  se utilizas el leftJoin para traer resultado independientemente si existen resultados en una tabla u otra,
         si se usa inner join se obtendra resultados precisos ya solo traera resultados cuando existan en las dos tablas relacionadas.
         Los ultimos dos wheres se utilizan para omitir los resultados de la tabla vacuna, ya que por defecto los trae y aumentaria el contador
         de aplicaciones de vacunas aplicada
        */
        $historialVacunas = Vacuna::selectRaw($sentenciaSql)
            ->leftJoin(
                'vacunacions',
                function (JoinClause $join) use ($ganado) {
                    $join->on('vacunas.id', '=', 'vacunacions.vacuna_id')
                        ->where('vacunacions.ganado_id', $ganado->id);
                }
            )
        ->leftJoin(
            'plan_sanitarios',
            function (JoinClause $join) use ($ganado) {
                $join->on('vacunas.id', '=', 'plan_sanitarios.vacuna_id')
                    ->where('plan_sanitarios.hacienda_id', session('hacienda_id'))
                    ->where('fecha_inicio', '>', $ganado->fecha_nacimiento ?? $ganado->created_at);
            }
        )
        ->where('plan_sanitarios.prox_dosis', '!=', 'null')
        ->orWhere('vacunacions.fecha', '!=', 'null')
        ->orderBy('fecha', 'desc')
        ->get()
        ->toArray();

        /**
 * @var array<string,<array{string}>> $vacunas
*/
        $vacunas = [];
        //agrupas vacunas por nombre y obtener todas las fechas de aplicacion de las fismas
        /**
 * return
*/
        foreach ($historialVacunas as $key => $vacuna) {
            $nombreVacuna = $vacuna['vacuna'];
            if (!array_key_exists($nombreVacuna, $vacunas)) {
                $vacunas = array_merge($vacunas, [$nombreVacuna => []]);
                array_push($vacunas[$nombreVacuna], $vacuna['fecha']);
            } else {
                array_push($vacunas[$nombreVacuna], $vacuna['fecha']);
            }
        }

        $dataPdf = [
        'numero' => $ventaGanado->ganado->numero ?? '',
        'tipo' => $ventaGanado->ganado->tipo->tipo,
        'peso' => $ventaGanado->ganado->peso->peso_actual,
        'comprador' => $ventaGanado->comprador->nombre,
        'vacunas' => $vacunas
        /*  'precio' => $ventaGanado->precio,
        'precioKg' => round($ventaGanado->precio / intval($ventaGanado->ganado->peso->peso_actual), 2), */
        ];

        return $this->generarPdf(VistasReporte::facturaVentaGanado,$dataPdf,"Nota de venta ganado " .  $ventaGanado->ganado->numero ?? '');
    }
}
