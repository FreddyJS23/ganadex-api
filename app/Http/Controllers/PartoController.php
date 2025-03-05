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
use App\Models\PartoCria;
use App\Models\Peso;
use App\Models\Toro;
use App\Traits\GuardarVeterinarioOperacionSegunRol;
use DateTime;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
                    },
                   'ganado_crias'
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


        try {
          DB::transaction(function () use($parto,$request) {

            $parto->save();

            $idTipoBecerro = GanadoTipo::where('tipo', 'becerro')->first()->id;
            $idEstadoSano=Estado::where('estado', 'sano')->first()->id;

            $idHaciendaSession = session('hacienda_id');

            foreach ($request->input('crias') as $cria ) {
                $nuevaCria = new Ganado();
                //evaluacion sera criado para toro
                if($cria['sexo']=='T') $nuevaCria->sexo='M';
                else $nuevaCria->sexo=$cria['sexo'];
                //campos cria
                $nuevaCria->numero = $cria['numero'];
                $nuevaCria->nombre = $cria['nombre'];
                $nuevaCria->fecha_nacimiento = $request->input('fecha');
                $nuevaCria->tipo_id =$idTipoBecerro;
                $nuevaCria->origen = 'local';
                $nuevaCria->hacienda_id = $idHaciendaSession;
                $nuevaCria->save();
                //eventos
                $nuevaCria->evento()->create();
                //estado sano
                $nuevaCria->estados()->attach($idEstadoSano);

                $peso_nacimiento = new Peso(['peso_nacimiento' => $cria['peso_nacimiento']]);
                $peso_nacimiento->ganado()->associate($nuevaCria)->save();

                //asociar cria al parto
                $partoCria=PartoCria::create([
                    'observacion' => $cria['observacion'] ?? null,
                    'ganado_id' => $nuevaCria->id,
                    'parto_id' => $parto->id,
                    'hacienda_id' => $idHaciendaSession
                ]);

                PartoHechoCriaToro::dispatchIf($cria['sexo']=='T',$partoCria);

            }
          });
        } catch (\Throwable $th) {
            return response()->json(['error' => 'error al insertar datos'], 501);
        }


        PartoHecho::dispatch($parto);

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
                    },
                    'ganado_crias'
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
