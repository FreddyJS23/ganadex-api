<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreToroRequest;
use App\Http\Requests\UpdateToroRequest;
use App\Http\Resources\ServiciosToroResource;
use App\Http\Resources\ToroCollection;
use App\Http\Resources\ToroResource;
use App\Models\Ganado;
use App\Models\GanadoTipo;
use App\Models\Plan_sanitario;
use App\Models\Servicio;
use App\Models\Toro;
use App\Models\Vacuna;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        $toros = Toro::where('hacienda_id', session('hacienda_id'))
            ->with(
                [
                'ganado',
                'padreEnPartos' => function (Builder $query) {
                    $query->orderBy('fecha', 'desc');
                },
                ]
            )
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

                    //se debe  usar raw ya que el modelo tiene un cast para las fechas en formar d-m-Y, pero para la query se usa el formato Y-m-d
                    $fechaInicio = $toro->padreEnpartos[1]->getRawOriginal("fecha");
                    $fechaFin = $toro->padreEnpartos[0]->getRawOriginal("fecha");

                    $toro->fechaInicio = $fechaInicio;
                    $toro->fechaFin = $fechaFin;

                    $toro->load(
                        ['servicios' => function (Builder $query) use ($fechaInicio, $fechaFin) {
                            $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                        }]
                    );

                    $toro->efectividad = $toro->servicios->count() >= 1 ?  $efectividad($toro->servicios->count()) : null;
                }
                return $toro;
            }
        );
        //return response()->json(['toros' => json_encode($toros->toArray())], 200);

        return new ToroCollection($toros);
    }


    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreToroRequest $request)
    {
        $ganado = new Ganado($request->all());
        $ganado->hacienda_id = session('hacienda_id');
        $ganado->sexo = "M";

        try {
            DB::transaction(
                function () use ($ganado, $request) {
                    $ganado->save();
                    //estado fallecido
                    $request->only('estado_id')['estado_id'][0] == 2 && $ganado->fallecimiento()->create(
                        [
                        'fecha' => $request->input('fecha_fallecimiento'),
                        'causas_fallecimiento_id' => $request->input('causas_fallecimiento_id'),
                        'descripcion' => $request->input('descripcion')

                        ]
                    );

                    //estado vendido
                    $request->only('estado_id')['estado_id'][0] == 5 && $ganado->venta()->create(
                        [
                        'fecha' => $request->input('fecha_venta'),
                        'precio' => $request->input('precio'),
                        'comprador_id' => $request->input('comprador_id'),
                        'hacienda_id' => session('hacienda_id')
                        ]
                    );
                    //vacunacion

                    $vacunas = [];

                    foreach ($request->only('vacunas')['vacunas'] as $vacuna) {
                        array_push($vacunas, $vacuna + ['ganado_id' => $ganado->id, 'hacienda_id' => $ganado->hacienda_id]);
                    }

                    $ganado->vacunaciones()->createMany($vacunas);

                    $ganado->estados()->sync($request->only('estado_id')['estado_id']);
                    $ganado->peso()->create($request->only($this->peso));

                    $ganado->evento()->create();
                }
            );
        } catch (\Throwable) {
            return response()->json(['error' => 'error al insertar datos'], 501);
        }

        $toro = new Toro();
        $toro->hacienda_id = session('hacienda_id');
        $toro->ganado()->associate($ganado)->save();

        return response()->json(['toro' => new ToroResource($toro)], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Toro $toro)
    {
        $toro
            ->load(
                [
                'padreEnPartos' => function (Builder $query) {
                    $query->orderBy('fecha', 'desc');
                },
                ]
            );

        //tabla principal relacional, para obtener el ganado
        $ganado=$toro->ganado;

        /*sentencia sql para poder generar un id a base de la fecha de creacion, si no se genera un id puede habe un error
        ya que pueden coincidir los id de la tabla vacunaciones y la de plan_sanitarioes*/
        /*Explicacion consulta: se evaluara y los id de algunas de las tablas es nulo,
        ademas se utliza un formato de la fecha (j, para obtener el numero del dia del año, ejem:034)
        ademas se le concatena el id de la tabla que no es nulo*/
        $sentenciaSqlGenerarId="CASE
            WHEN vacunacions.id IS NULL THEN CONCAT(DATE_FORMAT(plan_sanitarios.created_at, '%j'),plan_sanitarios.id)
            WHEN plan_sanitarios.created_at IS NULL THEN CONCAT(DATE_FORMAT(vacunacions.created_at, '%j'),vacunacions.id)
            ELSE CONCAT(DATE_FORMAT(plan_sanitarios.created_at, '%j'),plan_sanitarios.id)
        END";

        /*sentencia sql para poder obtener el historial de vacunaciones bien sea de las tablas vacunaciones o plan_sanitarioes*/
        $sentenciaSqlHistorialVacunas = "
        CONCAT(vacunas.id, $sentenciaSqlGenerarId) as id,
        nombre as vacuna,
        CASE
            WHEN vacunacions.prox_dosis IS NULL THEN plan_sanitarios.prox_dosis
            WHEN plan_sanitarios.prox_dosis IS NULL THEN vacunacions.prox_dosis
            ELSE plan_sanitarios.prox_dosis
        END as prox_dosis,
        CASE
            WHEN vacunacions.fecha is NULL THEN plan_sanitarios.fecha_inicio
            WHEN plan_sanitarios.fecha_inicio is NULL THEN vacunacions.fecha
        END as fecha
        ";

        /*  se utilizas el leftJoin para traer resultado independientemente si existen resultados en una tabla u otra,
        si se usa inner join se obtendra resultados precisos ya solo traera resultados cuando existan en las dos tablas relacionadas.
        Los ultimos dos wheres se utilizan para omitir los resultados de la tabla vacuna, ya que por defecto los trae y aumentaria el contador
        de aplicaciones de vacunas aplicada
        */
        $historialVacunas = Vacuna::selectRaw($sentenciaSqlHistorialVacunas)
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
        ->get();

        //diferencia dias entre proxima vacunacion individual y plan sanitario
        $diferencia = session('dias_diferencia_vacuna');
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
        $agruparVacunas = Vacuna::selectRaw($sentenciaSqlAgruparVacunas)
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
        ->get();

        $toro->efectividad = null;

        /*efectividad respecto a cuantos servicios hiso para que la vaca quede prenada */
        $efectividad = fn (int $resultadoAlcanzado) => round(1 / $resultadoAlcanzado * 100, 2);

        if ($toro->padreEnPartos->count() == 1) {
            $toro->load('servicios');

            $toro->efectividad = $efectividad($toro->servicios->count());
        } elseif ($toro->padreEnPartos->count() >= 2) {
             //se debe  usar raw ya que el modelo tiene un cast para las fechas en formar d-m-Y, pero para la query se usa el formato Y-m-d
             $fechaInicio = $toro->padreEnpartos[1]->getRawOriginal("fecha");
             $fechaFin = $toro->padreEnpartos[0]->getRawOriginal("fecha");

            $toro->fechaInicio = $fechaInicio;
            $toro->fechaFin = $fechaFin;

            $toro->load(
                ['servicios' => function (Builder $query) use ($fechaInicio, $fechaFin) {
                    $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                }]
            );

            $toro->efectividad = $toro->servicios->count() >= 1 ?  $efectividad($toro->servicios->count()) : null;
        }

        return response()->json(
            [
            'toro' => new ToroResource($toro),
            'vacunaciones' => (object)[
                'vacunas' => $agruparVacunas,
                'historial' => $historialVacunas
                ]
            ],
            200
        );
    }

    public function servicios(Toro $toro)
    {
        $servicios = $toro->servicios;
        return response()->json(['servicios' => ServiciosToroResource::collection($servicios)], 200);
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateToroRequest $request, Toro $toro)
    {
        $toro->ganado->fill($request->except($this->peso))->save();
        $toro->ganado->peso->fill($request->only($this->peso))->save();

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
