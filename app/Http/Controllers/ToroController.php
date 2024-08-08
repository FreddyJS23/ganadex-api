<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreToroRequest;
use App\Http\Requests\UpdateToroRequest;
use App\Http\Resources\ToroCollection;
use App\Http\Resources\ToroResource;
use App\Models\Ganado;
use App\Models\GanadoTipo;
use App\Models\Servicio;
use App\Models\Toro;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

use function Laravel\Prompts\select;

class ToroController extends Controller
{
    public array $peso = ['peso_nacimiento', 'peso_destete', 'peso_2year', 'peso_actual'];
    public function __construct()
    {
        $this->authorizeResource(Toro::class, 'toro');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $toros = Toro::where('user_id', Auth::id())
            ->with([
                'ganado' => function (Builder $query) {
                    $query->doesntHave('ganadoDescarte');
                },
                'padreEnPartos' => function (Builder $query) {
                    $query->orderBy('fecha', 'desc');
                },
            ])
            ->withCount('servicios')
            ->withCount('padreEnPartos')->get();

        $toros->transform(
            function (Toro $toro) {

                $toro->efectividad = null;

                /*efectividad respecto a cuantos servicios hiso para que la vaca quede prenada */
                $efectividad = fn (int $resultadoAlcanzado) => round(1 / $resultadoAlcanzado * 100, 2);

                if ($toro->padreEnPartos->count() == 1) {

                    $toro->load('servicios');

                    $toro->efectividad = $toro->servicios->count() >= 1 ? $efectividad($toro->servicios->count()) : null;
                } elseif ($toro->padreEnPartos->count() >= 2) {
                    $fechaInicio = $toro->padreEnPartos[1]->fecha;
                    $fechaFin = $toro->padreEnPartos[0]->fecha;

                    $toro->fechaInicio = $fechaInicio;
                    $toro->fechaFin = $fechaFin;

                    $toro->load(['servicios' => function (Builder $query) use ($fechaInicio, $fechaFin) {
                        $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                    }]);

                    $toro->efectividad = $toro->servicios->count() >= 1 ?  $efectividad($toro->servicios->count()) : null;
                }
                return $toro;
            }
        );

        return new ToroCollection($toros);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreToroRequest $request)
    {
        $ganado = new Ganado($request->all());
        $ganado->user_id = Auth::id();
        $ganado->tipo_id = GanadoTipo::where('tipo', 'adulto')->first()->id;
        $ganado->sexo = "M";
        $ganado->save();
        $ganado->peso()->create($request->only($this->peso));

        $toro = new Toro;
        $toro->user_id = Auth::id();
        $toro->ganado()->associate($ganado)->save();

        return response()->json(['toro' => new ToroResource($toro)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Toro $toro)
    {
        $toro
            ->load([
                'padreEnPartos' => function (Builder $query) {
                    $query->orderBy('fecha', 'desc');
                },
            ]);

        $toro->efectividad = null;

        /*efectividad respecto a cuantos servicios hiso para que la vaca quede prenada */
        $efectividad = fn (int $resultadoAlcanzado) => round(1 / $resultadoAlcanzado * 100, 2);

        if ($toro->padreEnPartos->count() == 1) {

            $toro->load('servicios');

            $toro->efectividad = $efectividad($toro->servicios->count());
        } elseif ($toro->padreEnPartos->count() >= 2) {
            $fechaInicio = $toro->padreEnPartos[1]->fecha;
            $fechaFin = $toro->padreEnPartos[0]->fecha;

            $toro->fechaInicio = $fechaInicio;
            $toro->fechaFin = $fechaFin;

            $toro->load(['servicios' => function (Builder $query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            }]);

            $toro->efectividad = $toro->servicios->count() >= 1 ?  $efectividad($toro->servicios->count()) : null;
        }

        return response()->json([
            'toro' => new ToroResource($toro),
        ], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateToroRequest $request, Toro $toro)
    {

        $toro->ganado->fill($request->all())->save();

        return response()->json(['toro' => new ToroResource($toro)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Toro $toro)
    {
        return  response()->json(['toroID' => Ganado::destroy($toro->ganado->id) ?  $toro->id : ''], 200);
    }
}
