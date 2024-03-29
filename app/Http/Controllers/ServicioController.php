<?php

namespace App\Http\Controllers;

use App\Events\ServicioHecho;
use App\Http\Requests\StoreServicioRequest;
use App\Http\Requests\UpdateServicioRequest;
use App\Http\Resources\ServicioCollection;
use App\Http\Resources\ServicioResource;
use App\Models\Ganado;
use App\Models\Servicio;
use DateTime;
use Illuminate\Contracts\Database\Eloquent\Builder;

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
        return new ServicioCollection(Servicio::whereBelongsTo($ganado)->with(['toro' => function (Builder $query) {
            $query->select('toros.id', 'numero')->join('ganados', 'ganado_id', '=', 'ganados.id');
        },
            'veterinario' => function (Builder $query) {
                $query->select('personals.id', 'nombre');
            }
        ])->get());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreServicioRequest $request,Ganado $ganado)
    {
        $fecha=new DateTime();
        $servicio=new Servicio;
        $toro=Ganado::firstWhere('numero',$request->input('numero_toro'))->toro;
        $servicio->fill($request->except(['numero_toro']));
        $servicio->fecha=$fecha->format('Y-m-d');
        $servicio->ganado()->associate($ganado);
        $servicio->toro()->associate($toro)->save();

        ServicioHecho::dispatch($servicio);
    
        return response()->json(['servicio'=>new ServicioResource($servicio->load(['toro' => function (Builder $query) {
            $query->select('toros.id', 'numero')->join('ganados', 'ganado_id', '=', 'ganados.id');
        }, 'veterinario' => function (Builder $query) {
            $query->select('personals.id', 'nombre');
        }]))],201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Ganado $ganado, Servicio $servicio)
    {
        return response()->json(['servicio'=>new ServicioResource($servicio->load(['toro' => function (Builder $query) {
            $query->select('toros.id', 'numero')->join('ganados', 'ganado_id', '=', 'ganados.id');
        }, 'veterinario' => function (Builder $query) {
            $query->select('personals.id', 'nombre');
        }]))]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServicioRequest $request,Ganado $ganado, Servicio $servicio)
    {
        $toro=Ganado::firstWhere('numero',$request->input('numero_toro'))->toro;
        $servicio->fill($request->except(['numero_toro']));
        $servicio->toro()->associate($toro)->save();

        return response()->json(['servicio'=> new ServicioResource($servicio->load(['toro' => function (Builder $query) {
            $query->select('toros.id', 'numero')->join('ganados', 'ganado_id', '=', 'ganados.id');
        }, 'veterinario' => function (Builder $query) {
            $query->select('personals.id', 'nombre');
        }]))],200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ganado $ganado, Servicio $servicio)
    {
        return  response()->json(['servicioID' => Servicio::destroy($servicio->id) ?  $servicio->id : ''], 200);
    }
}
