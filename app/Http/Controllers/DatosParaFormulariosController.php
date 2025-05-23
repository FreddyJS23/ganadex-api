<?php

namespace App\Http\Controllers;

use App\Http\Resources\CargosPersonalCollection;
use App\Http\Resources\NovillaAMontarCollection;
use App\Http\Resources\VeterinariosDisponiblesCollection;
use App\Models\Cargo;
use App\Models\Ganado;
use App\Models\Leche;
use App\Models\OrigenGanado;
use App\Models\Personal;
use App\Models\Peso;
use App\Models\Vacuna;
use App\Models\Venta;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DatosParaFormulariosController extends Controller
{
    public function novillasParaMontar()
    {
        $novillasAmontar = Peso::whereHas(
            'ganado',
            function (Builder $query) {
                $query->where('hacienda_id', session('hacienda_id'));
            }
        )->where('peso_actual', '>=', 330)->get();

        return new NovillaAMontarCollection($novillasAmontar);
    }
    public function cargosPersonalDisponible()
    {
        $cargos = Cargo::get();

        return new CargosPersonalCollection($cargos);
    }

    public function veterinariosDisponiblesHaciendaActual()
    {
        return new VeterinariosDisponiblesCollection(
            Personal::select('id', 'nombre')
                ->where('cargo_id', 2)
                ->where('user_id', Auth::id())
                ->whereRelation('haciendas', 'haciendas.id', session('hacienda_id'))
                ->get()
        );
    }

    public function veterinariosDisponibles()
    {
        return new VeterinariosDisponiblesCollection(
            Personal::select('id', 'nombre')
                ->where('cargo_id', 2)
                ->where('user_id', Auth::id())
                ->whereRelation('haciendas', 'haciendas.id','!=', session('hacienda_id'))
                ->get()
        );
    }

    public function obrerosDisponibles()
    {
        return response()->json(['obreros' => Personal::select('id', 'nombre')
            ->where('cargo_id', 1)
            ->where('user_id', Auth::id())
            ->whereRelation('haciendas', 'haciendas.id', session('hacienda_id'))
            ->get()],200);
    }

    public function añosVentasGanado()
    {
        $añosVentasGanado = Venta::where('hacienda_id', session('hacienda_id'))
            ->selectRaw('DATE_FORMAT(fecha,"%Y") as año')
            ->groupBy('año')
            ->orderBy('año', 'desc')
            ->get();

        $añosVentasGanado->transform(
            function ($item, $key) {
                $item->año = intval($item->año);
                return $item;
            }
        );

        return response()->json(['años_ventas_ganado' => $añosVentasGanado]);
    }
    public function añosProduccionLeche()
    {
        $añosVentaProduccionLeche = Leche::where('hacienda_id', session('hacienda_id'))
            ->selectRaw('DATE_FORMAT(fecha,"%Y") as año')
            ->groupBy('año')
            ->orderBy('año', 'desc')
            ->get();

        $añosVentaProduccionLeche->transform(
            function ($item, $key) {
                $item->año = intval($item->año);
                return $item;
            }
        );

        return response()->json(['años_produccion_leche' => $añosVentaProduccionLeche]);
    }

    public function vacunasDisponibles()
    {
        $vacunasDisponibles = Vacuna::with('tiposGanado')->get()->makeHidden(["created_at", "updated_at"]);

        $vacunasDisponibles->transform(function (Vacuna $item) {
            $item->tipos_ganado = $item->tiposGanado->map(function ($tipoGanado) {
                return [
                    'tipo' => $tipoGanado->tipo,
                    'sexo' => $tipoGanado->pivot->sexo,
                ];
            });
            //Elimina la relación original tiposGanado para evitar redundancia en la respuesta.
            unset($item->tiposGanado);
            return $item;
        });

        return response()->json(['vacunas_disponibles' => $vacunasDisponibles]);
    }

    public function sugerirNumeroDisponibleEnBD()
    {
        $numeroSugerido = null;
        $intervalos = [1, 500, 501, 5000, 5001, 10000, 10001, 15000, 15001, 25000, 25001, 32767];
        $iteracciones = 0;
        $maximaInteraciones = 100;
        $punteroInicial = 0;

        while ($numeroSugerido == null) {
            $numeroRandom = random_int($intervalos[$punteroInicial], $intervalos[$punteroInicial + 1]);
            $numeroYaRegistrado = Ganado::select('numero')
                ->where('numero', $numeroRandom)->first();

            if ($numeroYaRegistrado == null) {
                $numeroSugerido = $numeroRandom;
                break;
            }
            $iteracciones++;

            if ($iteracciones >= $maximaInteraciones) {
                $iteracciones = 0;
                $punteroInicial++;
            }
        }
        return response()->json(['numero_disponible' => $numeroSugerido]);
    }

    public function veterinariosSinUsuario()
    {
        $veterinariosSinUsuario = Personal::where('cargo_id', 2)
            ->select('id', 'nombre')
            ->where('user_id', Auth::id())
            ->whereRelation('haciendas', 'haciendas.id', session('hacienda_id'))
            ->whereDoesntHave('usuarioVeterinario')
            ->get();

        return response()->json(['veterinarios_sin_usuario' => $veterinariosSinUsuario]);
    }

    public function origenGanado()
    {
        return response()->json(['origen_ganado' => OrigenGanado::select('id', 'origen')->get()], 200);
    }
}
