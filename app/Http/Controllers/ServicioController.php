<?php

namespace App\Http\Controllers;

use App\Events\ServicioHecho;
use App\Http\Requests\StoreServicioRequest;
use App\Http\Requests\UpdateServicioRequest;
use App\Http\Resources\ServicioCollection;
use App\Http\Resources\ServicioResource;
use App\Models\Ganado;
use App\Models\PajuelaToro;
use App\Models\Servicio;
use App\Models\Toro;
use DateTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ServicioController extends Controller
{
     public function __construct()
    {
        $this->authorizeResource(Servicio::class,'servicio');
    }   

    /**
     * Display a listing of the resource.
     */
    public function index(Ganado $ganado)
    {
        return new ServicioCollection(Servicio::whereBelongsTo($ganado)
        ->with(['servicioable' => function (MorphTo $morphTo) {
           $morphTo->morphWith([Toro::class=>'ganado:id,numero',PajuelaToro::class]);
        },
            'veterinario' => function (Builder $query) {
                $query->select('personals.id', 'nombre');
            }
        ])->get()
    );
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServicioRequest $request,Ganado $ganado)
    {
        $fecha=new DateTime();
        $servicio=new Servicio;
        $servicio->fill($request->except(['toro_id','pajuela_toro_id']));
        $servicio->fecha=$fecha->format('Y-m-d');
        $servicio->ganado()->associate($ganado);
        /**
         *@var 'monta' | 'inseminacion'  */
        $tipoServicio=$request->input('tipo');

        if($tipoServicio == 'monta') {
            $toro = Toro::find($request->input('toro_id'));
            $servicio->servicioable()->associate($toro);
        }
        elseif($tipoServicio == 'inseminacion') {
            $pajuelaToro = PajuelaToro::find($request->input('pajuela_toro_id'));
            $servicio->servicioable()->associate($pajuelaToro);
        }
        $servicio->save();
         ServicioHecho::dispatch($servicio);

        return response()->json(['servicio'=>new ServicioResource($servicio->load(['veterinario' => function (Builder $query) {
            $query->select('personals.id', 'nombre');
        }]
        )->loadMorph('servicioable', [Toro::class => 'ganado:id,numero', PajuelaToro::class])
        )],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ganado $ganado, Servicio $servicio)
    {
        return response()->json(['servicio' => new ServicioResource(
            $servicio->load(
                ['veterinario' => function (Builder $query) {
                    $query->select('personals.id', 'nombre');
                }]
            )->loadMorph('servicioable', [Toro::class => 'ganado:id,numero', PajuelaToro::class])
        )], 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServicioRequest $request,Ganado $ganado, Servicio $servicio)
    {
        $servicio->fill($request->except(['toro_id', 'pajuela_toro_id']));

        /**
         *@var 'monta' | 'inseminacion'  */
        $tipoServicio = $request->input('tipo');
        if ($tipoServicio == 'monta') {
            $toro = Toro::find($request->input('toro_id'));
            $servicio->servicioable()->associate($toro);
        } elseif ($tipoServicio == 'inseminacion') {
            $pajuelaToro = PajuelaToro::find($request->input('pajuela_toro_id'));
            $servicio->servicioable()->associate($pajuelaToro);
        }
        $servicio->save();

        return response()->json(['servicio' => new ServicioResource(
            $servicio->load(
                ['veterinario' => function (Builder $query) {
                    $query->select('personals.id', 'nombre');
                }]
            )->loadMorph('servicioable', [Toro::class => 'ganado:id,numero', PajuelaToro::class])
        )], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ganado $ganado, Servicio $servicio)
    {
        return  response()->json(['servicioID' => Servicio::destroy($servicio->id) ?  $servicio->id : ''], 200);
    }
}
