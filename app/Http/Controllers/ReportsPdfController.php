<?php

namespace App\Http\Controllers;

use App\Models\Fallecimiento;
use App\Models\Ganado;
use App\Models\GanadoTipo;
use App\Models\Leche;
use App\Models\Personal;
use App\Models\Peso;
use App\Models\Venta;
use App\Models\VentaLeche;
use Barryvdh\DomPDF\Facade\Pdf;
use DateTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class ReportsPdfController extends Controller
{
  public function resumenGanado(Ganado $ganado)
  {
    $ganado->load('peso', 'tipo', 'estados');
    $ganado->loadCount('servicios')->loadCount('revision')->loadCount('parto');
    $ultimaRevision = $ganado->revisionReciente;
    $ultimoServicio = $ganado->servicioReciente;
    $ultimoParto = $ganado->partoReciente;
    $ultimoPesajeLeche = $ganado->pesajeLecheReciente;
    $efectividad = fn (int $resultadoAlcanzado, int $resultadoPrevisto) => $resultadoAlcanzado * 100 / $resultadoPrevisto;

    $ganadoInfo = $ganado->only(['nombre', 'numero', 'sexo', 'origen', 'fecha_nacimiento']);
    $ganadoInfo = array_merge($ganadoInfo, ['tipo' => $ganado->tipo->tipo]);

    $ganadoPeso = Arr::except($ganado->peso->toArray(), ['id']);

    $resumenServicio = $ultimoServicio ? [
      'ultimo' => $ultimoServicio->fecha,
      'toro' => $ultimoServicio->toro->ganado->numero,
      'efectividad' => $ganado->parto_count ? round($efectividad($ganado->parto_count, $ganado->servicios_count), 2) : null,
      'total' => $ganado->servicios_count,
    ] : [];




$resumenRevision = $ultimaRevision ? [
      'ultimo' => $ultimaRevision->fecha,
      'Diagnostico' => $ultimaRevision->diagnostico,
      'total' => $ganado->revision_count,
    ] : [];


    $resumenParto = $ultimoParto ? [
      'ultimo' => $ultimoParto->fecha,
      'peso_cria' => $ultimoParto->ganado_cria->peso->peso_nacimiento,
      'nombre/numero' => $ultimoParto->ganado_cria->numero ? $ultimoParto->ganado_cria->numero : $ultimoParto->ganado_cria->nombre,
      'total' => $ganado->parto_count,
    ] : [];


$estadoProduccionLeche= $ganado->estados->contains('estado','lactancia') ? "En producción" : 'Inactiva';

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
    ];

    $pdf = Pdf::loadView('ganadoReporte', $dataPdf);

    return $pdf->stream();
  }


  public function resumenGeneral()
  {
    function obtenerSumaTotalPorTipo(array $tipo):int{
        $sumaTotalPorTipo = 0;
        foreach ($tipo as $key => $value) $sumaTotalPorTipo += $value['cantidad'];
        return $sumaTotalPorTipo;
    }


    $fechaActual = new DateTime();
    $mesActual = $fechaActual->format('m');
    session()->put('finca_id', 1);

    /* ------------------------------ obtene vacas ------------------------------ */
    //tambien abarca las que seran futuras vacas
    $vacas = Ganado::where('finca_id', session('finca_id'))
      ->doesntHave('toro')
      ->doesntHave('ganadoDescarte')
      ->selectRaw('tipo, COUNT(tipo) as cantidad')
      ->join('ganado_tipos', 'tipo_id', 'ganado_tipos.id')
      ->orderBy('tipo_id')
      ->groupBy('tipo')
      ->get()
      ->toArray();

      $totalVacasEnProduccion = Ganado::select('id')->where('finca_id',session('finca_id'))
            ->whereRelation('estados', 'estado', 'lactancia')
            ->count();
    array_push($vacas, ['tipo' => 'total','cantidad' => obtenerSumaTotalPorTipo($vacas)]);
    array_push($vacas, ['tipo' => 'productiva','cantidad' => $totalVacasEnProduccion]);
    /* ------------------------------ obtene toros ------------------------------ */
    //tambien abacar los que seran futuros toros
    $toros
      = Ganado::where('finca_id', session('finca_id'))
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
      = Ganado::where('finca_id', session('finca_id'))
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
    $ganadoMortalida =Fallecimiento::selectRaw('COUNT(id) as total')->whereRelation('ganado','finca_id',session('finca_id'))
    ->whereYear('fecha',$fechaActual->format('Y'))
    ->first()
    ->total;

   $ganadoNatalidad =Ganado::selectRaw('COUNT(id) as total')
   ->whereYear('fecha_nacimiento',$fechaActual->format('Y'))
   ->where('finca_id', session('finca_id'))
   ->first()->total;

   /* ------------------------ obtener top vacas productoras y menos productoras ------------------------- */
    $topVacasProductoras = Leche::withWhereHas('ganado', function ($query) {
      $query->where('finca_id', session('finca_id'))
        ->select('id', 'numero');
    })->orderBy('peso_leche', 'desc')
      ->whereMonth('fecha', $mesActual)
      ->select('peso_leche', 'ganado_id')
      ->limit(3)
      ->get();

    $ordernarArrayVacasProductoras = [];
    foreach ($topVacasProductoras as $key => $vaca) {
      array_push($ordernarArrayVacasProductoras,  [
        'numero' => $vaca->ganado->numero,
        'peso_leche' => $vaca->peso_leche
      ]);;
    }
    $topVacasProductoras = $ordernarArrayVacasProductoras;

    $ordernarArrayVacasMenosProductoras = [];
    $topVacasMenosProductoras = Leche::withWhereHas('ganado', function ($query) {
      $query->where('finca_id', session('finca_id'))->select('id', 'numero');
    })->orderBy('peso_leche', 'asc')
      ->whereMonth('fecha', $mesActual)
      ->limit(3)
      ->get();

    foreach ($topVacasMenosProductoras as $key => $vaca) {
      array_push($ordernarArrayVacasMenosProductoras,  [
        'numero' => $vaca->ganado->numero,
        'peso_leche' => $vaca->peso_leche
      ]);
    }
    $topVacasMenosProductoras = $ordernarArrayVacasMenosProductoras;

    /* ------------------------ obtener total vacas en gestacio,revision y servicio ------------------------- */
    $totalVacasEnGestacion = Ganado::where('finca_id',session('finca_id'))
      ->whereRelation('estados', 'estado', 'gestacion')
      ->count();

    $totalGanadoPendienteRevision = Ganado::where('finca_id',session('finca_id'))
      ->whereRelation('estados', 'estado', 'pendiente_revision')
      ->count();

    $novillasAmontar =Ganado::where('finca_id',session('finca_id'))
    ->whereRelation('estados', 'estado', 'pendiente_servicio')
    ->count();

    $ganadoPendienteAcciones = [
      'revision' => $totalGanadoPendienteRevision,
      'servir' => $novillasAmontar,
      'preñadas' => $totalVacasEnGestacion,
    ];

    /* ------------------------ obtener total personal ------------------------- */
    $totalPersonal = Personal::where('finca_id',session('finca_id'))
      ->selectRaw('cargo, COUNT(cargo) as cantidad')
      ->join('cargos', 'cargo_id', 'cargos.id')
      ->groupBy('cargo')
      ->get()
      ->toArray();

      $personal=[];
      $cantidadTotalPersonal=0;
      foreach ($totalPersonal as $key => $value) {
        $cantidadTotalPersonal+=$value['cantidad'];
        $personal=array_merge($personal,[$value['cargo'] =>$value['cantidad']]);
      }
      $personal=array_merge($personal,['total'=>$cantidadTotalPersonal]);
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
      /** @var array{'mes':string,'promedio_pesaje':float'}  */
      $infoMesFormateado = ['mes' => $mes, 'promedio_pesaje' => $promedioPesaje];

      $mesPrimerSemestre ? array_push($balancePrimerSemestre, $infoMesFormateado) : array_push($balanceSegundoSemestre, $infoMesFormateado);
    }

    /* -------------------- relleno de datos para el reporte -------------------- */
    $dataPdf = [
      'vacas' => $vacas,
      'toros' => $toros,
      'ganadoDescarte' => $ganadoDescarte,
      'natalidad'=>strval($ganadoNatalidad),
      'mortalidad'=>strval($ganadoMortalida),
      'topVacasProductoras' => $topVacasProductoras,
      'topVacasMenosProductoras' => $topVacasMenosProductoras,
      'ganadoPendienteAcciones' => $ganadoPendienteAcciones,
      'totalPersonal' => $personal,
      'balancePrimerSemestre' => $balancePrimerSemestre,
      'balanceSegundoSemestre' => $balanceSegundoSemestre
    ];

    //return view('resumenGeneralReporte', $dataPdf);
      $pdf = Pdf::loadView('resumenGeneralReporte', $dataPdf);

    return $pdf->stream();
  }

  public function resumenVentasLeche(Request $request)
  {
$inicio=$request->query('start');
$fin=$request->query('end');

    $ventasLeche = VentaLeche::where('finca_id',session('finca_id'))
      ->oldest('fecha')
      ->select('venta_leches.fecha', 'cantidad', 'precio')
      ->selectRaw('(cantidad * precio) AS ganancia_total')
      ->join('precios', 'precio_id', 'precios.id')
      ->whereBetween('venta_leches.fecha', [$inicio, $fin])
      ->get()
      ->toArray();

    $dataPdf = ['ventasLeche' => $ventasLeche,
    'inicio'=>$inicio,
    'fin'=>$fin,];

    $pdf = Pdf::loadView('resumenVentasLeche', $dataPdf);

    return $pdf->stream();
  }


  public function resumenVentaGanadoAnual(Request $request)
  {

    $year = $request->query('year');

  $ventasGanado = Venta::where('finca_id',session('finca_id'))
      ->join('ganados', 'ganado_id', 'ganados.id')
      //->selectRaw("DATE_FORMAT(fecha,'%m') as mes,numero,precio")
      ->selectRaw("DATE_FORMAT(fecha,'%m') as mes,numero")
      ->orderBy('mes', 'asc')
      ->whereYear('fecha', $year)
      ->get();

    $ventasGanado->transform(function (Venta $item, int $key) {
      $meses = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
      $item->mes = $meses[intval($item->mes)];
      return $item;
    });

    $dataPdf = ['ventasGanado' => $ventasGanado->groupBy('mes')->toArray(),'year'=>$year];

    $pdf = Pdf::loadView('resumenVentaGanadoAnual', $dataPdf);

    return $pdf->stream();
  }

  public function resumenCausasFallecimientos(Request $request)
  {
    $inicio = $request->query('start');
    $fin = $request->query('end');

    $fallecimientos = Fallecimiento::whereRelation('ganado', 'finca_id', session('finca_id'))
      ->selectRaw('causa, COUNT(causa) AS cantidad')
      ->orderby('cantidad', 'desc')
      ->groupBy('causa')
      ->whereBetween('fallecimientos.fecha', [$inicio, $fin])
      ->get();

    $cantidadOtrasCausas = 0;

    foreach ($fallecimientos as $key => $item) {
      $key > 3 && $cantidadOtrasCausas += $item->cantidad;
    };

    $fallecimientos = $fallecimientos->filter(function (Fallecimiento $item, int $key) {

      if ($key < 3)  return $item;
    })->toArray();

    array_push($fallecimientos, ['causa' => 'otras_causas', 'cantidad' => $cantidadOtrasCausas]);

    $dataPdf = ['causasFallecimientos' => $fallecimientos];

    $pdf = Pdf::loadView('resumenCausasFallecimientos',$dataPdf);

    return $pdf->stream();

  }

  public function facturaVentaGanado(){

    $ventaGanado = Venta::where('finca_id',session('finca_id'))
    ->with(['ganado'=>['peso'],'comprador'])
      ->orderBy('fecha', 'desc')
      ->first();


    $dataPdf = [
      'numero' => $ventaGanado->ganado->numero,
      'tipo' => $ventaGanado->ganado->tipo->tipo,
      'peso' => $ventaGanado->ganado->peso->peso_actual,
      'comprador' => $ventaGanado->comprador->nombre,
      'precio' => $ventaGanado->precio,
      'precioKg' => round($ventaGanado->precio / intval($ventaGanado->ganado->peso->peso_actual), 2),
  ];

    $pdf = Pdf::loadView('notaVentaGanado', $dataPdf);

   return $pdf->stream();
  }
}
