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
use App\Models\Vacunacion;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\JsonResponse;

class GanadoController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Ganado::class,'ganado');
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
            ->doesntHave('fallecimiento')
            ->doesntHave('venta')
            ->doesntHave('ganadoDescarte')
            ->where('finca_id',[session('finca_id')])
            ->with(['peso','evento','estados'])
            ->get());
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
                DB::transaction(function () use ($ganado, $request) {
                    $ganado->save();
                    //estado fallecido
                    $request->only($this->estado)['estado_id'][0] == 2 && $ganado->fallecimiento()->create(
                        [
                            'fecha' => $request->input('fecha_fallecimiento'),
                            'causa' => $request->input('causa')
                        ]
                    );

                    //estado vendido
                    $request->only($this->estado)['estado_id'][0] == 5 && $ganado->venta()->create([
                        'fecha' => $request->input('fecha_venta'),
                        'precio' => $request->input('precio'),
                        'comprador_id' => $request->input('comprador_id'),
                        'finca_id' => session('finca_id')

                    ]);

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
                });
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

        $jornadasVacunacionAnteriores =  Jornada_vacunacion::where('finca_id',[session('finca_id')])
        ->select('jornada_vacunacions.id','nombre as vacuna', 'fecha_inicio as fecha','prox_dosis')
        ->join('vacunas', 'jornada_vacunacions.vacuna_id', 'vacunas.id')
        ->orderBy('fecha', 'desc')
        ->where('fecha_inicio','>',$ganado->fecha_nacimiento ?? $ganado->created_at)
        ->get();


        $ultimaRevision = $ganado->revisionReciente;
        $ultimoServicio = $ganado->servicioReciente;
        $ultimoPesajeLeche = $ganado->pesajeLecheReciente;
        $ultimoParto = $ganado->partoReciente;
        $ganado->load(['parto'=>function (Builder $query) {
            $query->orderBy('fecha','desc');
            }, 'vacunaciones'=>function (Builder $query) {
                $query->select('vacunacions.id','nombre AS vacuna','fecha','ganado_id','prox_dosis')
                ->join('vacunas','vacunacions.vacuna_id','vacunas.id');
            }
    ])->loadCount('revision');

    //concatenando los datos de las vacunaciones con las jornadas de vacunacion posteriores
    $ganado->vacunaciones=$ganado->vacunaciones->makeHidden('ganado_id')->concat($jornadasVacunacionAnteriores);

    //ordeno todas las vacunaciones y jornadas concadenadas por fecha
    $ganado->vacunaciones=$ganado->vacunaciones->sortByDesc('fecha');
    //transformar el id para evitar duplicados de vacunaciones y jornadas vacunacion
    $ganado->vacunaciones=$ganado->vacunaciones->transform(function(Vacunacion|Jornada_vacunacion $item,int $key){
        $item->id=$item->id . $item->prox_dosis;
        return $item;
    });

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

            $ganado->load(['servicios' => function (Builder $query) use ($fechaInicio, $fechaFin) {
                $query->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            }]);

            $ganado->efectividad = $ganado->servicios->count() >= 1 ?  $efectividad($ganado->servicios->count()) : null;
        }


        $mejorPesajesLeche = Leche::where('ganado_id', $ganado->id)->orderBy('peso_leche', 'desc')->first();
        $peorPesajesLeche = Leche::where('ganado_id', $ganado->id)->orderBy('peso_leche', 'asc')->first();
        $estadoProduccionLeche = $ganado->estados->contains('estado', 'lactancia') ? "En producción" : 'Inactiva';

        return response()->json([
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
            'vacunaciones' => $ganado->vacunaciones->values()->all()
        ], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateGanadoRequest $request, Ganado $ganado)
    {
        $ganado->fill($request->except($this->peso + $this->estado))->save();
        $ganado->peso->fill($request->only($this->peso))->save();
        $ganado->estados()->sync($request->only($this->estado)['estado_id']);

        return response()->json(['ganado'=>new GanadoResource($ganado)],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ganado $ganado)
    {
        return  response()->json(['ganadoID' => Ganado::destroy($ganado->id) ?  $ganado->id : ''], 200);
    }
}
