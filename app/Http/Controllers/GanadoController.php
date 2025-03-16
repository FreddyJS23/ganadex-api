<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreGanadoRequest;
use App\Http\Requests\UpdateGanadoRequest;
use App\Http\Resources\GanadoCollection;
use App\Http\Resources\GanadoResource;
use App\Http\Resources\LecheResource;
use App\Http\Resources\PartoResource;
use App\Http\Resources\RevisionResource;
use App\Http\Resources\ServicioResource;
use App\Models\Estado;
use App\Models\Ganado;
use App\Models\Plan_sanitario;
use App\Models\Leche;
use App\Models\Servicio;
use App\Models\Vacuna;
use App\Models\Vacunacion;
use Carbon\Carbon;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class GanadoController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Ganado::class, 'ganado');
    }


    public array $estado = ['estado_id'];
    public array $peso = ['peso_nacimiento', 'peso_destete','peso_2year','peso_actual'];
    public array $vendido = ['precio','comprador_id'];
    /**
     * Display a listing of the resource.
     */
    public function index(): ResourceCollection
    {
        return new GanadoCollection(
            Ganado::doesntHave('toro')
                ->doesntHave('ganadoDescarte')
                ->where('hacienda_id', [session('hacienda_id')])
                ->with(['peso','evento','estados'])
                ->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGanadoRequest $request): JsonResponse
    {
        $ganado = new Ganado();
        $ganado->sexo = "H";
        $ganado->fill($request->except($this->estado + $this->peso));
        $ganado->hacienda_id = session('hacienda_id');

        try {
                DB::transaction(
                    function () use ($ganado, $request) {
                        $ganado->save();
                        //estado fallecido
                        $request->only($this->estado)['estado_id'][0] == 2 && $ganado->fallecimiento()->create(
                            [
                            'fecha' => $request->input('fecha_fallecimiento'),
                            'causas_fallecimiento_id' => $request->input('causas_fallecimiento_id')
                            ]
                        );

                        //estado vendido
                        $request->only($this->estado)['estado_id'][0] == 5 && $ganado->venta()->create(
                            [
                            'fecha' => $request->input('fecha_venta'),
                            'precio' => $request->input('precio'),
                            'comprador_id' => $request->input('comprador_id'),
                            'hacienda_id' => session('hacienda_id')

                            ]
                        );

                        $ganado->peso()->create($request->only($this->peso));
                        //$ganado->peso()->create($request->only($this->peso));
                        $ganado->estados()->sync($request->only($this->estado)['estado_id']);

                        //vacunacion

                        $vacunas = [];

                        foreach ($request->only('vacunas')['vacunas'] as $vacuna) {
                            array_push($vacunas, $vacuna + ['ganado_id' => $ganado->id, 'hacienda_id' => $ganado->hacienda_id]);
                        }

                        $ganado->vacunaciones()->createMany($vacunas);

                        $ganado->evento()->create();
                    }
                );
                return response()->json(['ganado' => new GanadoResource($ganado)], 201);
        } catch (\Throwable) {
            return response()->json(['error' => 'error al insertar datos'], 501);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Ganado $ganado)
    {

        $planesSanitarioAnteriores =  Plan_sanitario::where('hacienda_id', [session('hacienda_id')])
            ->select('plan_sanitarios.id', 'nombre as vacuna', 'fecha_inicio as fecha', 'prox_dosis')
            ->join('vacunas', 'plan_sanitarios.vacuna_id', 'vacunas.id')
            ->orderBy('fecha', 'desc')
            ->where('fecha_inicio', '>', $ganado->fecha_nacimiento ?? $ganado->created_at)
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

        $ultimaRevision = $ganado->revisionReciente;
        $ultimoServicio = $ganado->servicioReciente;
        $ultimoParto = $ganado->partoReciente;
        $ganado->load(
            ['parto' => function (Builder $query) {
                $query->orderBy('fecha', 'desc');
            }, 'vacunaciones' => function (Builder $query) {
                $query->select('vacunacions.id', 'nombre AS vacuna', 'fecha', 'ganado_id', 'prox_dosis')
                    ->join('vacunas', 'vacunacions.vacuna_id', 'vacunas.id');
            }
            ]
        )->loadCount('revision');

        //concatenando los datos de las vacunaciones con las planes sanitarios posteriores
        $ganado->vacunaciones = $ganado->vacunaciones->makeHidden('ganado_id')->concat($planesSanitarioAnteriores);

        //ordeno todas las vacunaciones y planes concadenadas por fecha
        $ganado->vacunaciones = $ganado->vacunaciones->sortByDesc('fecha');
        //transformar el id para evitar duplicados de vacunaciones y planes sanitarios
        $ganado->vacunaciones = $ganado->vacunaciones->transform(
            function (Vacunacion|Plan_sanitario $item, int $key) {
                $item->id = $item->id . $item->prox_dosis;
                return $item;
            }
        );

        /*efectividad respecto a cuantos servicios fueron necesarios para que la vaca quede prenada */
        $efectividad = fn (int $resultadoAlcanzado) => round(1 / $resultadoAlcanzado * 100, 2);

        if ($ganado->parto->count() == 1) {
            $ganado->load('servicios');

            $ganado->efectividad = $efectividad($ganado->servicios->count());
       /* para la efectivida se obtienen los servicios entre el penultimo parto y el ultimo parto,
       queriendo decir que no se hace una efectividad de todos los servicios que se hicieron en el ganado*/
        } elseif ($ganado->parto->count() >= 2) {
            $fechaInicio = $ganado->parto[1]->fecha;
            $fechaFin = $ganado->parto[0]->fecha;

            $ganado->fechaInicio = $fechaInicio;
            $ganado->fechaFin = $fechaFin;

            $ganado->load(
                ['servicios' => function (Builder $query) use ($fechaInicio, $fechaFin) {
                    $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                }]
            );

            $ganado->efectividad = $ganado->servicios->count() >= 1 ?  $efectividad($ganado->servicios->count()) : null;
        }


        /* ------------------------ datos de pesajes de leche ----------------------- */
        $pesajesDeLeche = Leche::where('ganado_id', $ganado->id)->orderBy('peso_leche', 'desc')->get();

        $tienePesajesDeLeche = $pesajesDeLeche->count() > 0;

        if($tienePesajesDeLeche){

            $datosAcumulados= function (Collection $pesajesDeLeche):array
            {
                $produccionAcumuladaLeche = 0;
                $diasEnProduccionLeche = 0;

                //el uso de &$produccionAcumuladaLeche es para que el valor de produccionAcumuladaLeche se actualice en cada iteracion
                $pesajesDeLeche->each(function (Leche $pesaje) use (&$produccionAcumuladaLeche, &$diasEnProduccionLeche) {
                    $fecha=new Carbon($pesaje->fecha);
                    $mes=$fecha->month;

                    //meses con 31 dias
                    if($mes==1 || $mes==3 || $mes==5 || $mes==7 || $mes==8 || $mes==10 || $mes==12)
                    {
                    $produccionAcumuladaLeche += $pesaje->peso_leche * 31;
                    $diasEnProduccionLeche += 31;
                    }

                    //meses con 30 dias
                    if($mes==4 || $mes==6 || $mes==9 || $mes==11)
                    {
                    $produccionAcumuladaLeche += $pesaje->peso_leche * 30;
                    $diasEnProduccionLeche += 31;

                    }

                    //mes febrero
                    if($mes==2)
                    {
                    $añoBiciencio=$fecha->isLeapYear();
                    $diasMes=$añoBiciencio ? 29 : 28;
                    $produccionAcumuladaLeche += $pesaje->peso_leche * $diasMes;
                    $diasEnProduccionLeche += $diasMes;
                    }
                });

                return ['dias_produccion' => $diasEnProduccionLeche, 'produccion_acumulada' => $produccionAcumuladaLeche];
            };


            $mejorPesajesLeche = $pesajesDeLeche->first();
            $peorPesajesLeche = $pesajesDeLeche->last();
            $promedioPesajesLeche =$pesajesDeLeche->avg('peso_leche');
            //convertir el array para obtener como variables las propiedades dias_produccion y produccion_acumulada
            extract($datosAcumulados($pesajesDeLeche));

            $ultimoPesajeLeche = $ganado->pesajeLecheReciente;
        }

        $estadoProduccionLeche = $ganado->estados->contains('estado', 'lactancia') ? "En producción" : 'Inactiva';

        return response()->json(
            [
            'ganado' => new GanadoResource($ganado),
            'servicio_reciente' => $ultimoServicio ? new ServicioResource($ultimoServicio) : null,
            'total_servicios' => $ganado->servicios->count(),
            'total_servicios_acumulados' => Servicio::where('ganado_id', $ganado->id)->count(),
            'revision_reciente' => $ultimaRevision ? new RevisionResource($ultimaRevision) : null,
            'total_revisiones' => $ganado->revision_count,
            'parto_reciente' => $ultimoParto ? new PartoResource($ultimoParto) : null,
            'total_partos' => $ganado->parto->count(),
            'efectividad' => $ganado->efectividad,
            'info_pesajes_leche' => (object)([
                'reciente' => $tienePesajesDeLeche ? new LecheResource($ultimoPesajeLeche) : null ,
                'mejor' => $tienePesajesDeLeche ? new LecheResource($mejorPesajesLeche) : null ,
                'peor' => $tienePesajesDeLeche ? new LecheResource($peorPesajesLeche) : null ,
                'promedio' =>$tienePesajesDeLeche ? $promedioPesajesLeche . '%' : null ,
                'produccion_acumulada' =>$tienePesajesDeLeche ? $produccion_acumulada : null ,
                /* los dias en produccion son un aproximado donde se suman todos los dias del mes de pesaje de leche
                siendo asi puede que haya ocaciones que se haya hecho el pesaje pero la vaca no termino de dar leche el resto del mes */
                'dias_produccion' =>$tienePesajesDeLeche ?  $dias_produccion  : null ,
                'estado' => $estadoProduccionLeche
            ]),
            'vacunaciones' => (object)[
                'vacunas' => $agruparVacunas,
                'historial' => $ganado->vacunaciones->values()->all()
                ]
            ],
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGanadoRequest $request, Ganado $ganado)
    {
        $ganado->fill($request->except($this->peso + $this->estado))->save();
        $ganado->peso->fill($request->only($this->peso))->save();
        //$ganado->estados()->sync($request->only($this->estado)['estado_id']);

        return response()->json(['ganado' => new GanadoResource($ganado)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ganado $ganado)
    {
        return  response()->json(['ganadoID' => Ganado::destroy($ganado->id) ?  $ganado->id : ''], 200);
    }
}
