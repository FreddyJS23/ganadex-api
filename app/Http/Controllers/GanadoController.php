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
use App\Models\Jornada_vacunacion;
use App\Models\Leche;
use App\Models\Vacuna;
use App\Models\Vacunacion;
use Illuminate\Contracts\Database\Query\Builder;
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


    public array $estado=['estado_id'];
    public array $peso=['peso_nacimiento', 'peso_destete','peso_2year','peso_actual'];
    public array $vendido=['precio','comprador_id'];
    /**
     * Display a listing of the resource.
     */
    public function index() :ResourceCollection
    {
        return new GanadoCollection(
            Ganado::doesntHave('toro')
                ->doesntHave('ganadoDescarte')
                ->where('finca_id', [session('finca_id')])
                ->with(['peso','evento','estados'])
                ->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreGanadoRequest $request) :JsonResponse
    {
        $ganado=new Ganado;
        $ganado->sexo = "H";
        $ganado->fill($request->except($this->estado + $this->peso));
        $ganado->finca_id=session('finca_id');

        try {
                DB::transaction(
                    function () use ($ganado, $request) {
                        $ganado->save();
                        //estado fallecido
                        $request->only($this->estado)['estado_id'][0] == 2 && $ganado->fallecimiento()->create(
                            [
                            'fecha' => $request->input('fecha_fallecimiento'),
                            'causa' => $request->input('causa')
                            ]
                        );

                        //estado vendido
                        $request->only($this->estado)['estado_id'][0] == 5 && $ganado->venta()->create(
                            [
                            'fecha' => $request->input('fecha_venta'),
                            'precio' => $request->input('precio'),
                            'comprador_id' => $request->input('comprador_id'),
                            'finca_id' => session('finca_id')

                            ]
                        );

                        $ganado->peso()->create($request->only($this->peso));
                        //$ganado->peso()->create($request->only($this->peso));
                        $ganado->estados()->sync($request->only($this->estado)['estado_id']);

                        //vacunacion

                        $vacunas=[];

                        foreach ($request->only('vacunas')['vacunas'] as $vacuna) {
                            array_push($vacunas, $vacuna + ['ganado_id' => $ganado->id, 'finca_id' => $ganado->finca_id]);
                        }

                        $ganado->vacunaciones()->createMany($vacunas);

                        $ganado->evento()->create();
                    }
                );
                return response()->json(['ganado' => new GanadoResource($ganado)], 201);
        } catch (\Throwable $error) {

            return response()->json(['error'=>'error al insertar datos'], 501);
        }

    }

    /**
     * Display the specified resource.
     */
    public function show(Ganado $ganado)
    {

        $jornadasVacunacionAnteriores =  Jornada_vacunacion::where('finca_id', [session('finca_id')])
            ->select('jornada_vacunacions.id', 'nombre as vacuna', 'fecha_inicio as fecha', 'prox_dosis')
            ->join('vacunas', 'jornada_vacunacions.vacuna_id', 'vacunas.id')
            ->orderBy('fecha', 'desc')
            ->where('fecha_inicio', '>', $ganado->fecha_nacimiento ?? $ganado->created_at)
            ->get();


        //diferencia dias entre proxima vacunacion individual y jornada vacunacion
        $diferencia=session('dias_diferencia_vacuna');
        $setenciaDiferenciaDias="DATEDIFF(MAX(jornada_vacunacions.prox_dosis),MAX(vacunacions.prox_dosis))";

        /* Explicacion consulta
        usar un alias para las vacunas.
        primer case: para comprobar ver si alguna de las tablas relacionadas no existe registro,
        si existe registro en las dos se hace una suma de +1 ya que al contar los registros al existir en las dos tablas hace el conteo como 1.
        segundo case: determinar la proxima dosis dependiendo la existencia de registros en las tablas relacionadas,
        si existen registros en las dos tablas se comprueba que proxima dosis se le debe dar prioridad
        tercer case: determinar la ultima vacunacion dependiendo la existencia de registros en las tablas relacionadas,
        si existen registros en las dos tablas se comprueba que ultima dosis es la mas reciente*/
        $sentenciaSqlAgruparVacunas="nombre as vacuna,
        CASE
            WHEN MAX(vacunacions.prox_dosis) IS NULL OR MAX(jornada_vacunacions.prox_dosis) IS NULL THEN COUNT(nombre)
            ELSE COUNT(nombre) + 1
            END as cantidad,
        CASE
            WHEN MAX(vacunacions.prox_dosis) IS NULL THEN MAX(jornada_vacunacions.prox_dosis)
            WHEN MAX(jornada_vacunacions.prox_dosis) IS NULL THEN MAX(vacunacions.prox_dosis)
            WHEN $setenciaDiferenciaDias >= $diferencia THEN MAX(vacunacions.prox_dosis)
            ELSE MAX(jornada_vacunacions.prox_dosis)
        END as prox_dosis,
        CASE
            WHEN MAX(vacunacions.fecha) IS NULL THEN MAX(jornada_vacunacions.fecha_inicio)
            WHEN MAX(jornada_vacunacions.fecha_inicio) IS NULL THEN MAX(vacunacions.fecha)
            WHEN MAX(jornada_vacunacions.fecha_inicio) > MAX(vacunacions.fecha) THEN MAX(jornada_vacunacions.fecha_inicio)
            ELSE MAX(vacunacions.fecha)
        END as ultima_dosis
        ";

        /*  se utilizas el leftJoin para traer resultado independientemente si existen resultados en una tabla u otra,
        si se usa inner join se obtendra resultados precisos ya solo traera resultados cuando existan en las dos tablas relacionadas.
        Los ultimos dos wheres se utilizan para omitir los resultados de la tabla vacuna, ya que por defecto los trae y aumentaria el contador
        de aplicaciones de vacunas aplicada
        */
        $agruparVacunas=Vacuna::selectRaw($sentenciaSqlAgruparVacunas)
            ->leftJoin(
                'vacunacions', function (JoinClause $join) use ($ganado) {
                    $join->on('vacunas.id', '=', 'vacunacions.vacuna_id')
                        ->where('vacunacions.ganado_id', $ganado->id);
                }
            )
        ->leftJoin(
            'jornada_vacunacions', function (JoinClause $join) use ($ganado) {
                $join->on('vacunas.id', '=', 'jornada_vacunacions.vacuna_id')
                    ->where('jornada_vacunacions.finca_id', session('finca_id'))
                    ->where('fecha_inicio', '>', $ganado->fecha_nacimiento ?? $ganado->created_at);
            }
        )
        ->where('jornada_vacunacions.prox_dosis', '!=', 'null')
        ->orWhere('vacunacions.prox_dosis', '!=', 'null')
        ->groupBy('nombre')
        ->get();

        $ultimaRevision = $ganado->revisionReciente;
        $ultimoServicio = $ganado->servicioReciente;
        $ultimoPesajeLeche = $ganado->pesajeLecheReciente;
        $ultimoParto = $ganado->partoReciente;
        $ganado->load(
            ['parto'=>function (Builder $query) {
                $query->orderBy('fecha', 'desc');
            }, 'vacunaciones'=>function (Builder $query) {
                $query->select('vacunacions.id', 'nombre AS vacuna', 'fecha', 'ganado_id', 'prox_dosis')
                    ->join('vacunas', 'vacunacions.vacuna_id', 'vacunas.id');
            }
            ]
        )->loadCount('revision');

        //concatenando los datos de las vacunaciones con las jornadas de vacunacion posteriores
        $ganado->vacunaciones=$ganado->vacunaciones->makeHidden('ganado_id')->concat($jornadasVacunacionAnteriores);

        //ordeno todas las vacunaciones y jornadas concadenadas por fecha
        $ganado->vacunaciones=$ganado->vacunaciones->sortByDesc('fecha');
        //transformar el id para evitar duplicados de vacunaciones y jornadas vacunacion
        $ganado->vacunaciones=$ganado->vacunaciones->transform(
            function (Vacunacion|Jornada_vacunacion $item,int $key) {
                $item->id=$item->id . $item->prox_dosis;
                return $item;
            }
        );

        /*efectividad respecto a cuantos servicios fueron necesarios para que la vaca quede prenada */
        $efectividad = fn (int $resultadoAlcanzado) => round(1 / $resultadoAlcanzado * 100, 2);

        if ($ganado->parto->count() == 1) {

            $ganado->load('servicios');

            $ganado->efectividad = $efectividad($ganado->servicios->count());
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


        $mejorPesajesLeche = Leche::where('ganado_id', $ganado->id)->orderBy('peso_leche', 'desc')->first();
        $peorPesajesLeche = Leche::where('ganado_id', $ganado->id)->orderBy('peso_leche', 'asc')->first();
        $estadoProduccionLeche = $ganado->estados->contains('estado', 'lactancia') ? "En producción" : 'Inactiva';

        return response()->json(
            [
            'ganado' => new GanadoResource($ganado),
            'servicio_reciente' => $ultimoServicio ? new ServicioResource($ultimoServicio) : null,
            'total_servicios' => $ganado->servicios->count(),
            'revision_reciente' => $ultimaRevision ? new RevisionResource($ultimaRevision) : null,
            'total_revisiones' => $ganado->revision_count,
            'parto_reciente' => $ultimoParto ? new PartoResource($ultimoParto) : null,
            'total_partos' => $ganado->parto->count(),
            'efectividad' => $ganado->efectividad,
            'info_pesajes_leche' => (object)([
                'reciente' => $ultimoPesajeLeche ? new LecheResource($ultimoPesajeLeche) : null,
                'mejor' => $ultimoPesajeLeche ? new LecheResource($mejorPesajesLeche) : null,
                'peor' => $ultimoPesajeLeche ? new LecheResource($peorPesajesLeche) : null,
                'estado' => $estadoProduccionLeche
            ]),
            'vacunaciones' =>(object)[
                'vacunas'=>$agruparVacunas,
                'historial'=>$ganado->vacunaciones->values()->all()
                ]
            ], 200
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

        return response()->json(['ganado'=>new GanadoResource($ganado)], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ganado $ganado)
    {
        return  response()->json(['ganadoID' => Ganado::destroy($ganado->id) ?  $ganado->id : ''], 200);
    }
}
