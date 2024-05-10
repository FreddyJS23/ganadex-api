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
   
    $fechaActual = new DateTime();
    $mesActual = $fechaActual->format('m');

    $TotalGanadoPorTiposMacho = Ganado::where('user_id', Auth::id())
      ->where('sexo', 'M')
      ->selectRaw('tipo, COUNT(tipo) as cantidad')
      ->join('ganado_tipos', 'tipo_id', 'ganado_tipos.id')
      ->groupBy('tipo')
      ->get()
      ->toArray();

    //total de ganado macho
    $totalMacho = 0;
    foreach ($TotalGanadoPorTiposMacho as $key => $value) $totalMacho += $value['cantidad'];
    array_push($TotalGanadoPorTiposMacho, ['tipo' => 'total', 'cantidad' => $totalMacho]);

    $TotalGanadoPorTiposHembra
      = Ganado::where('user_id', Auth::id())
      ->where('sexo', 'H')
      ->selectRaw('tipo, COUNT(tipo) as cantidad')
      ->join('ganado_tipos', 'tipo_id', 'ganado_tipos.id')
      ->groupBy('tipo')
      ->get()
      ->toArray();

    //total de ganado hembra
    $totalHembra = 0;
    foreach ($TotalGanadoPorTiposHembra as $key => $value) $totalHembra += $value['cantidad'];
    array_push($TotalGanadoPorTiposHembra, ['tipo' => 'total', 'cantidad' => $totalHembra]);

    $topVacasProductoras = Leche::withWhereHas('ganado', function ($query) {
      $query->where('user_id', Auth::id())
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
      $query->where('user_id', Auth::id())->select('id', 'numero');
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

    $totalVacasEnGestacion = Ganado::whereBelongsTo(Auth::user())
      ->whereRelation('estados', 'estado', 'gestacion')
      ->count();

    $totalGanadoPendienteRevision = Ganado::whereBelongsTo(Auth::user())
      ->whereRelation('estados', 'estado', 'pendiente_revision')
      ->count();

    $novillasAmontar = Peso::whereHas('ganado', function (Builder $query) {
      $query->where('user_id', Auth::id());
    })->where('peso_actual', '>=', 330)->count();

    $ganadoPendienteAcciones = [
      'revision' => $totalGanadoPendienteRevision,
      'servir' => $novillasAmontar,
      'preñadas' => $totalVacasEnGestacion,
    ];


    $totalPersonal = Personal::whereBelongsTo(Auth::user())
      ->selectRaw('cargo, COUNT(cargo) as cantidad')
      ->join('cargos', 'cargo_id', 'cargos.id')
      ->groupBy('cargo')
      ->get()
      ->toArray();

    $balanceAnualLeche = Leche::selectRaw("DATE_FORMAT(fecha,'%m') as mes")
      ->selectRaw("AVG(peso_leche) as promedio_pesaje")
      ->groupBy('fecha')
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

    $dataPdf = [
      'tiposGanadoMacho' => $TotalGanadoPorTiposMacho,
      'tiposGanadoHembra' => $TotalGanadoPorTiposHembra,
      'topVacasProductoras' => $topVacasProductoras,
      'topVacasMenosProductoras' => $topVacasMenosProductoras,
      'ganadoPendienteAcciones' => $ganadoPendienteAcciones,
      'totalPersonal' => $totalPersonal,
      'balancePrimerSemestre' => $balancePrimerSemestre,
      'balanceSegundoSemestre' => $balanceSegundoSemestre
    ];

    $pdf = Pdf::loadView('resumenGeneralReporte', $dataPdf);
    
    return $pdf->stream();
  }

  public function resumenVentasLeche(Request $request)
  {
$inicio=$request->query('start');
$fin=$request->query('end');

    $ventasLeche = VentaLeche::whereBelongsTo(Auth::user())
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
    
  $ventasGanado = Venta::whereBelongsTo(Auth::user()) 
      ->join('ganados', 'ganado_id', 'ganados.id')
      ->selectRaw("DATE_FORMAT(fecha,'%m') as mes,numero,precio")
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

  public function resumenCausasFallecimientos()
  {

    Auth::loginUsingId(1);

    $fallecimientos = Fallecimiento::whereRelation('ganado', 'user_id', Auth::id())
      ->selectRaw('causa, COUNT(causa) AS cantidad')
      ->orderby('cantidad', 'desc')
      ->groupBy('causa')
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

    $ventaGanado = Venta::whereBelongsTo(Auth::user())
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
