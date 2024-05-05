<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreToroRequest;
use App\Http\Requests\UpdateToroRequest;
use App\Http\Resources\ToroCollection;
use App\Http\Resources\ToroResource;
use App\Models\Ganado;
use App\Models\GanadoTipo;
use App\Models\Toro;
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
        return new ToroCollection(Toro::all()->where('user_id', Auth::id()));
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
        $toro->loadCount('servicios')->loadCount('padreEnPartos');
        $efectividad = fn (int $resultadoAlcanzado, int $resultadoPrevisto) => $resultadoAlcanzado * 100 / $resultadoPrevisto;

        return response()->json([
            'toro' => new ToroResource($toro),
            'efectividad' => $toro->padre_en_partos_count ? round($efectividad($toro->padre_en_partos_count, $toro->servicios_count),2) : null,
            'padre_en_partos'=>$toro->padre_en_partos_count,
            'servicios'=>$toro->servicios_count,
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
