<?php

namespace App\Http\Controllers;

use App\Events\RevisionAborto;
use App\Events\RevisionDescarte;
use App\Events\RevisionPrenada;
use App\Http\Requests\StoreRevisionRequest;
use App\Http\Requests\UpdateRevisionRequest;
use App\Http\Resources\RevisionCollection;
use App\Http\Resources\RevisionResource;
use App\Models\Ganado;
use App\Models\Revision;
use App\Models\Servicio;
use App\Models\Toro;
use App\Traits\GuardarVeterinarioOperacionSegunRol;
use Carbon\Carbon;
use DateTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class RevisionController extends Controller
{
    use GuardarVeterinarioOperacionSegunRol;

    public function __construct()
    {
        $this->authorizeResource(Revision::class, 'revision');
    }
    /**
     * Display a listing of the resource.
     */
    public function index(Ganado $ganado)
    {
        return new RevisionCollection(
            Revision::whereBelongsTo($ganado)->with(
                ['tipoRevision',
                'veterinario' => function (Builder $query) {
                    $query->select('personals.id', 'nombre');
                },'ganado.evento'
                ]
            )
            ->orderBy('fecha','desc')
            ->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRevisionRequest $request, Ganado $ganado)
    {
        $diasFeto = $request->input('dias_feto');

        /* si la vaca esta en gestacion y no se tuvo un control del servicio, se creara un servicio monta de emergencia */
        if($request->servicio_desconocido){

            $toro = Toro::find($request->input('toro_id'));
            $servicio = new Servicio();
            $servicio->tipo='monta';
            $servicio->ganado()->associate($ganado);
            $fechaActual = Carbon::now();
            $servicio->fecha = $fechaActual->subDays($diasFeto)->format('Y-m-d');
            $servicio->servicioable()->associate($toro);
            $servicio->observacion='Servicio no registrado';
            $servicio->save();
        }
        $revision = new Revision();
        $revision->fill($request->except(['personal_id','proxima','servicio_desconocido','dias_feto','toro_id']));
        $revision->personal_id=$this->veterinarioOperacion($request);
        $revision->vacuna_id = $request->vacuna_id;
        $revision->ganado()->associate($ganado)->save();

        if($request->proxima){

           if($ganado->evento){
                $ganado->evento->prox_revision = $request->proxima;
                $ganado->evento->save();
            }else{
                $ganado->evento()->create(['prox_revision' => $request->proxima]);
            }
        }

         RevisionPrenada::dispatchIf($revision->tipo_revision_id == 1, $revision);

         RevisionDescarte::dispatchIf($revision->tipo_revision_id == 2, $revision);

         RevisionAborto::dispatchIf($revision->tipo_revision_id == 3, $revision);

        return response()->json(
            ['revision' => new RevisionResource(
                $revision->load(
                    ['veterinario' => function (Builder $query) {
                        $query->select('personals.id', 'nombre');
                    },'ganado.evento'
                    ]
                )
            )],
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Ganado $ganado, Revision $revision)
    {
        return response()->json(
            ['revision' => new RevisionResource(
                $revision->load(
                    ['veterinario' => function (Builder $query) {
                        $query->select('personals.id', 'nombre');
                    },'ganado.evento'
                    ]
                )
            )]
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRevisionRequest $request, Ganado $ganado, Revision $revision)
    {
        $revision->fill($request->all());
        $revision->save();

        return response()->json(
            ['revision' => new RevisionResource(
                $revision->load(
                    ['veterinario' => function (Builder $query) {
                        $query->select('personals.id', 'nombre');
                    },'ganado.evento'
                    ]
                )
            )],
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ganado $ganado, Revision $revision)
    {
        return  response()->json(['revisionID' => Revision::destroy($revision->id) ?  $revision->id : ''], 200);
    }
}
