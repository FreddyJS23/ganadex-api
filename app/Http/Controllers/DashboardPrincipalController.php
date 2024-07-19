<?php

namespace App\Http\Controllers;

use App\Http\Resources\BalanceAnualLecheCollection;
use App\Http\Resources\CantidadInsumoResource;
use App\Http\Resources\TopVacasMenosProductorasCollection;
use App\Http\Resources\TopVacasProductorasCollection;
use App\Http\Resources\TotalGanadoTipoCollection;
use App\Models\Ganado;
use App\Models\GanadoDescarte;
use App\Models\GanadoTipo;
use App\Models\Insumo;
use App\Models\Leche;
use App\Models\Personal;
use App\Models\Peso;
use DateTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardPrincipalController extends Controller
{
    public function totalGanadoTipo()
    {
        $totalGanadoPorTiposMacho = GanadoTipo::withCount(['ganado' => function (Builder $query) {
            $query->where('sexo','M')
            ->where('user_id', Auth::id())
            ->doesntHave('fallecimiento')
            ->doesntHave('venta')
            ->doesntHave('ganadodescarte');
        }])->get();
       
        $totalGanadoPorTiposHembra = GanadoTipo::withCount(['ganado' => function (Builder $query) {
            $query->where('sexo','H')
            ->where('user_id', Auth::id())
            ->doesntHave('fallecimiento')
            ->doesntHave('venta')
            ->doesntHave('ganadodescarte');
        }])->get();

        //Cambiar tipo ganado macho por tipo ganado hembra
        $totalGanadoPorTiposHembra->transform(function (GanadoTipo $item) {
            $item->tipo = substr($item->tipo, 0, -1) . 'a';
            return $item;
        }); 

        $totalGanadoDescarte = GanadoDescarte::where('user_id', Auth::id())->count();
        $totalGanadoDescarte=collect(    
           [[  'tipo'=>'descarte',
            'ganado_count' => $totalGanadoDescarte]]);

        return  new TotalGanadoTipoCollection($totalGanadoPorTiposHembra->concat($totalGanadoPorTiposMacho)->concat($totalGanadoDescarte));
    }

    public function totalPersonal(Request $request)
    {
        return response()->json(['total_personal' => Personal::whereBelongsTo(Auth::user())->count()]);
    }

    public function vacasEnGestacion(Request $request)
    {
        $totalVacasEnGestacion = Ganado::whereBelongsTo(Auth::user())
            ->whereRelation('estados', 'estado', 'gestacion')
            ->count();

        return response()->json(['vacas_en_gestacion' => $totalVacasEnGestacion], 200);
    }

    public function topVacasProductoras()
    {
        $fechaActual = new DateTime();
        $mesActual = $fechaActual->format('m');
        $topVacasProductoras = Leche::withWhereHas('ganado', function ( $query) {
            $query->where('user_id', Auth::id())->select('id','numero');
        })->orderBy('peso_leche', 'desc')->whereMonth('fecha', $mesActual)->limit(3)->get();



        return new TopVacasProductorasCollection($topVacasProductoras);
    }

    public function topVacasMenosProductoras()
    {
        $fechaActual = new DateTime;
        $mesActual = $fechaActual->format('m');
        $topVacasMenosProductoras = Leche::withWhereHas('ganado', function ($query) {
            $query->where('user_id', Auth::id())->select('id', 'numero');
        })->orderBy('peso_leche', 'asc')->whereMonth('fecha', $mesActual)->limit(3)->get();

        return new TopVacasMenosProductorasCollection($topVacasMenosProductoras);
    }

    public function totalGanadoPendienteRevision(Request $request)
    {

        $totalGanadoPendienteRevision = Ganado::whereBelongsTo(Auth::user())
            ->whereRelation('estados', 'estado', 'pendiente_revision')
            ->count();

        return response()->json(['ganado_pendiente_revision' => $totalGanadoPendienteRevision], 200);
    }

    public function cantidadVacasParaServir()
    {
        $novillasAmontar = Peso::whereHas('ganado', function (Builder $query) {
            $query->where('user_id', Auth::id());
        })->where('peso_actual', '>=', 330)->count();

        return response()->json(['cantidad_vacas_para_servir' => $novillasAmontar], 200);
    }

    public function insumoMenorExistencia()
    {
        $menorCantidadInsumo = Insumo::whereBelongsTo(Auth::user())->orderBy('cantidad', 'asc')->first();

        return response()->json(['menor_cantidad_insumo' =>$menorCantidadInsumo ? new CantidadInsumoResource($menorCantidadInsumo) : null]);
    }

    public function insumoMayorExistencia()
    {
        $mayorCantidadInsumo = Insumo::whereBelongsTo(Auth::user())->orderBy('cantidad', 'desc')->first();

        return response()->json(['mayor_cantidad_insumo' =>$mayorCantidadInsumo ? new CantidadInsumoResource($mayorCantidadInsumo) : null]);
    }

    public function balanceAnualProduccionLeche(Request $request)
    {
        $regexYear = "/^[2][0-9][0-9][0-9]$/";

        $year = preg_match($regexYear, $request->query('year')) ? $request->query('year') : now()->format('Y');
        
        $balanceAnualLeche = Leche::selectRaw("DATE_FORMAT(fecha,'%m') as mes")
        ->selectRaw("AVG(peso_leche) as promedio_mensual")
        ->whereYear('fecha',$year)
        ->groupBy('mes')->get();

        
        return new BalanceAnualLecheCollection($balanceAnualLeche);
    }
}
