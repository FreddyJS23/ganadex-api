<?php

namespace App\Http\Controllers;

use App\Events\NaceMacho;
use App\Events\PartoHecho;
use App\Events\PartoHechoCriaToro;
use App\Http\Requests\StorePartoRequest;
use App\Http\Requests\UpdatePartoRequest;
use App\Http\Resources\PartoCollection;
use App\Http\Resources\PartoResource;
use App\Models\Estado;
use App\Models\Ganado;
use App\Models\GanadoTipo;
use App\Models\PajuelaToro;
use App\Models\Parto;
use App\Models\Peso;
use App\Models\Toro;
use App\Traits\GuardarVeterinarioOperacionSegunRol;
use DateTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;

class PartoController extends Controller
{
    use GuardarVeterinarioOperacionSegunRol;

    /**
     * Display a listing of the resource.
     */
    public function index(Ganado $ganado)
    {
        return new PartoCollection(
            Parto::whereBelongsTo($ganado)
                ->with(
                    [
                    'partoable' => function (MorphTo $morphTo) {
                        $morphTo->morphWith([Toro::class => 'ganado:id,numero', PajuelaToro::class]);
                    },
                    'personal' => function (Builder $query) {
                        $query->select('personals.id', 'nombre','cargo')
                        ->join('cargos', 'cargo_id', 'cargos.id');
                    }
                    ]
                )->get()
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePartoRequest $request, Ganado $ganado)
    {
        $parto = new Parto();
        $parto->fill($request->only(['observacion','fecha']));
        $parto->personal_id=$this->veterinarioOperacion($request);
        $servicio = $ganado->servicioReciente->servicioable;

        $parto->ganado()->associate($ganado);
        $parto->partoable()->associate($servicio);

        $cria = new Ganado();

        $cria->fill($request->except(['observacion','peso_nacimiento','sexo']));

        //evaluacion sera criado para toro
        if($request->input('sexo')=='T') $cria->sexo='M';

        else $cria->sexo=$request->input('sexo');


        $cria->fecha_nacimiento = $request->input('fecha');
        $cria->tipo_id = GanadoTipo::where('tipo', 'becerro')->first()->id;
        $cria->origen = 'local';
        $cria->hacienda_id = session('hacienda_id');
        $cria->save();
        $cria->evento()->create();

        $estados = Estado::select('id')
            ->whereIn('estado', ['sano'])
            ->get()
            ->modelKeys();
        $cria->estados()->sync($estados);

        $peso_nacimiento = new Peso($request->only(['peso_nacimiento']));
        $peso_nacimiento->ganado()->associate($cria)->save();

        $parto->ganado_cria()->associate($cria)->save();

        PartoHecho::dispatch($parto);

        PartoHechoCriaToro::dispatchIf($request->input('sexo')=='T',$parto);

        return response()->json(
            ['parto' => new PartoResource(
                $parto->load(
                    ['personal' => function (Builder $query) {
                        $query->select('personals.id', 'nombre','cargo')
                        ->join('cargos', 'cargo_id', 'cargos.id');
                    }
                    ]
                )->loadMorph('partoable', [Toro::class => 'ganado:id,numero', PajuelaToro::class])
            )],
            201
        );
    }

    /**
     * Display the specified resource.
     */
    public function show(Ganado $ganado, Parto $parto)
    {
        return response()->json(
            ['parto' => new PartoResource(
                $parto->load(
                    [
                    'personal' => function (Builder $query) {
                        $query->select('personals.id', 'nombre','cargo')
                        ->join('cargos', 'cargo_id', 'cargos.id');
                    }
                    ]
                )->loadMorph('partoable', [Toro::class => 'ganado:id,numero', PajuelaToro::class])
            )],
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePartoRequest $request, Ganado $ganado, Parto $parto)
    {
        $parto->fill($request->only(['observacion']))->save();

        return response()->json(
            ['parto' => new PartoResource(
                $parto->load(
                    [
                    'personal' => function (Builder $query) {
                        $query->select('personals.id', 'nombre','cargo')
                        ->join('cargos', 'cargo_id', 'cargos.id');
                    }
                    ]
                )->loadMorph('partoable', [Toro::class => 'ganado:id,numero', PajuelaToro::class])
            )],
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Ganado $ganado, Parto $parto)
    {
        return  response()->json(['partoID' => Parto::destroy($parto->id) ?  $parto->id : ''], 200);
    }
}
